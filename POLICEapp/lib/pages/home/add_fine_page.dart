import 'dart:async';
import 'dart:io';

import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:record/record.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../services/violation_pdf_service.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/section_header.dart';

class AddViolationPage extends StatefulWidget {
  const AddViolationPage({super.key});

  @override
  State<AddViolationPage> createState() => _AddViolationPageState();
}

class _AddViolationPageState extends State<AddViolationPage> {
  final _formKey = GlobalKey<FormState>();

  final plateController = TextEditingController();
  final ownerController = TextEditingController();

  // ✅ OCR will fill these
  final modelController = TextEditingController();
  final colorController = TextEditingController();

  final cityNameController = TextEditingController();
  final streetController = TextEditingController();
  final landmarkController = TextEditingController();
  final descriptionController = TextEditingController();

  // ✅ STT transcript يظهر هنا (قابل للتعديل)
  final transcriptController = TextEditingController();

  String? selectedCityId; // ✅ start as null
  String? selectedViolationTypeId; // ✅ start as null

  List<Map<String, dynamic>> cities = [];
  List<Map<String, dynamic>> violationTypes = [];

  bool _loading = false;
  bool _lookupsLoading = false;
  String? _lookupsError;
  bool _locationLoading = false;
  String? _locationError;
  double? _latitude;
  double? _longitude;
  String? _detectedCityName;

  // 🔹 OCR variables
  File? _selectedImage;
  final ImagePicker _picker = ImagePicker();
  bool _ocrLoading = false;

  // ================= STT =================
  final AudioRecorder _recorder = AudioRecorder();
  bool _sttRecording = false;
  bool _sttLoading = false;
  String? _recordedPath;
  Timer? _sttTimer;
  Duration _recordDuration = Duration.zero;

  bool _sttCancelled = false;

  @override
  void initState() {
    super.initState();
    _loadLookups();
  }

  @override
  void dispose() {
    plateController.dispose();
    ownerController.dispose();
    modelController.dispose();
    colorController.dispose();
    cityNameController.dispose();
    streetController.dispose();
    landmarkController.dispose();
    descriptionController.dispose();
    transcriptController.dispose();

    _sttTimer?.cancel();
    _recorder.dispose();

    super.dispose();
  }

  // ================= Lookups =================
  Future<void> _loadLookups() async {
    final l10n = AppLocalizations.of(context);
    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.loginRequired'))),
      );
      return;
    }

    if (mounted) {
      setState(() {
        _lookupsLoading = true;
        _lookupsError = null;
      });
    }

    try {
      final dynamic cRaw = await ApiService.getCities(token);
      final dynamic tRaw = await ApiService.getViolationTypes(token);
      if (!mounted) return;

      final c = cRaw is Map ? Map<String, dynamic>.from(cRaw)['data'] : cRaw;
      final t = tRaw is Map ? Map<String, dynamic>.from(tRaw)['data'] : tRaw;
      final normalizedCities = _normalizeLookup(c ?? []);
      final normalizedViolationTypes = _normalizeLookup(t ?? []);
      final safeCities = normalizedCities.isNotEmpty
          ? normalizedCities
          : _normalizeLookup(ApiService.fallbackCities());
      final safeViolationTypes = normalizedViolationTypes.isNotEmpty
          ? normalizedViolationTypes
          : _normalizeLookup(ApiService.fallbackViolationTypes());

      setState(() {
        cities = safeCities;
        violationTypes = safeViolationTypes;

        // ✅ لا تختاري أول عنصر تلقائيًا (هذا كان سبب أن dropdown يطلع غلط)
        // selectedCityId = cities.isNotEmpty ? cities[0]['id'].toString() : null;
        // selectedViolationTypeId = violationTypes.isNotEmpty ? violationTypes[0]['id'].toString() : null;

        // لو بدك: خليهم null دائمًا حتى يجي STT أو المستخدم يختار
        selectedCityId = null;
        selectedViolationTypeId = null;
        _lookupsLoading = false;
        _lookupsError = null;
      });

      debugPrint(
        'AddViolationPage: loaded ${violationTypes.length} violation types',
      );
      debugPrint(
        'AddViolationPage: loaded ${cities.length} cities',
      );
      if (violationTypes.isEmpty && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(l10n.tr('addFine.violationTypeListUnavailable')),
          ),
        );
      }

      unawaited(_detectCurrentLocation());
    } catch (e) {
      if (!mounted) return;
      setState(() {
        cities = _normalizeLookup(ApiService.fallbackCities());
        violationTypes = _normalizeLookup(ApiService.fallbackViolationTypes());
        selectedCityId = null;
        selectedViolationTypeId = null;
        _lookupsLoading = false;
        _lookupsError = null;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            l10n.tr('addFine.lookupsError', params: {'error': '$e'}),
          ),
        ),
      );
    }
  }

  List<Map<String, dynamic>> _normalizeLookup(List<dynamic> raw) {
    final out = <Map<String, dynamic>>[];

    for (final item in raw) {
      final normalizedItem = _normalizeLookupItem(item);
      if (normalizedItem != null) {
        out.add(normalizedItem);
        continue;
      }

      if (item is String && item.trim().isNotEmpty) {
        final s = item.trim();
        out.add({'id': s, 'name': s});
      }
    }

    return out;
  }

  Map<String, dynamic>? _normalizeLookupItem(dynamic item) {
    final map = item is Map<String, dynamic>
        ? item
        : item is Map
            ? Map<String, dynamic>.from(item)
            : null;
    if (map == null) return null;

    final id = map['id'] ?? map['value'] ?? map['code'];
    if (id == null) return null;

    final normalized = Map<String, dynamic>.from(map);
    normalized['id'] = id;
    normalized['name'] = _firstNonEmptyLookupValue([
          map['name'],
          map['title'],
          map['label'],
          map['display_name'],
          map['violation_type_name'],
          map['city_name'],
          id,
        ]) ??
        id.toString();

    return normalized;
  }

  String? _firstNonEmptyLookupValue(List<dynamic> values) {
    for (final value in values) {
      final text = value?.toString().trim();
      if (text != null && text.isNotEmpty && text.toLowerCase() != 'null') {
        return text;
      }
    }
    return null;
  }

  int? _toIntOrNull(String? value) {
    if (value == null || value.trim().isEmpty) return null;
    return int.tryParse(value.trim());
  }

  String? _lookupNameById(
      List<Map<String, dynamic>> items, String? selectedId) {
    if (selectedId == null || selectedId.isEmpty) return null;

    for (final item in items) {
      if (item['id']?.toString() == selectedId) {
        return item['name']?.toString();
      }
    }

    return null;
  }

  String _normalizeLookupText(String value) {
    return value
        .trim()
        .toLowerCase()
        .replaceAll('\u0623', '\u0627')
        .replaceAll('\u0625', '\u0627')
        .replaceAll('\u0622', '\u0627')
        .replaceAll('\u0629', '\u0647')
        .replaceAll('\u0649', '\u064A');
  }

  bool _isLikelyInSyria(double latitude, double longitude) {
    return latitude >= 32.0 &&
        latitude <= 37.5 &&
        longitude >= 35.5 &&
        longitude <= 42.5;
  }

  String? _resolveCityNameForSubmit() {
    final manual = cityNameController.text.trim();
    if (manual.isNotEmpty) return manual;

    final selectedName = _lookupNameById(cities, selectedCityId)?.trim();
    if (selectedName != null && selectedName.isNotEmpty) return selectedName;

    final detected = _detectedCityName?.trim();
    if (detected != null && detected.isNotEmpty) return detected;

    return null;
  }

  String? _matchCityIdFromName(String? cityName) {
    if (cityName == null || cityName.trim().isEmpty) return null;

    final normalized = _normalizeLookupText(cityName);
    for (final item in cities) {
      final itemName = item['name']?.toString();
      if (itemName == null || itemName.trim().isEmpty) continue;

      final normalizedItem = _normalizeLookupText(itemName);
      if (normalizedItem == normalized ||
          normalizedItem.contains(normalized) ||
          normalized.contains(normalizedItem)) {
        return item['id']?.toString();
      }
    }

    return null;
  }

  String? _matchViolationTypeIdFromName(String? violationTypeName) {
    if (violationTypeName == null || violationTypeName.trim().isEmpty) {
      return null;
    }

    final normalized = _normalizeLookupText(violationTypeName);
    for (final item in violationTypes) {
      final itemName = item['name']?.toString();
      if (itemName == null || itemName.trim().isEmpty) continue;

      final normalizedItem = _normalizeLookupText(itemName);
      if (normalizedItem == normalized ||
          normalizedItem.contains(normalized) ||
          normalized.contains(normalizedItem)) {
        return item['id']?.toString();
      }
    }

    return null;
  }

  Future<void> _detectCurrentLocation() async {
    final l10n = AppLocalizations.of(context);
    if (_locationLoading) return;

    setState(() {
      _locationLoading = true;
      _locationError = null;
    });

    try {
      var serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        throw Exception(l10n.tr('addFine.locationServiceDisabled'));
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw Exception(l10n.tr('addFine.locationPermissionDenied'));
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      if (!_isLikelyInSyria(position.latitude, position.longitude)) {
        if (!mounted) return;
        setState(() {
          _locationLoading = false;
          _locationError = l10n.tr('addFine.locationOutsideSyria');
        });
        return;
      }

      final placemarks = await placemarkFromCoordinates(
        position.latitude,
        position.longitude,
      );

      final place = placemarks.isNotEmpty ? placemarks.first : null;
      final detectedCity = place?.locality ??
          place?.subAdministrativeArea ??
          place?.administrativeArea;
      final detectedStreet = place?.street ?? place?.thoroughfare;
      final detectedLandmark = [
        place?.subLocality,
        place?.name,
      ]
          .whereType<String>()
          .map((e) => e.trim())
          .where((e) => e.isNotEmpty)
          .join(', ');

      final matchedCityId = _matchCityIdFromName(detectedCity);

      setState(() {
        _latitude = position.latitude;
        _longitude = position.longitude;
        _detectedCityName =
            detectedCity?.trim().isEmpty ?? true ? null : detectedCity?.trim();
        _locationLoading = false;
        _locationError = null;

        if (matchedCityId != null) {
          selectedCityId = matchedCityId;
        }

        if ((cityNameController.text).trim().isEmpty &&
            _detectedCityName != null) {
          cityNameController.text = _detectedCityName!;
        }

        if ((streetController.text).trim().isEmpty &&
            detectedStreet != null &&
            detectedStreet.trim().isNotEmpty) {
          streetController.text = detectedStreet.trim();
        }

        if ((landmarkController.text).trim().isEmpty &&
            detectedLandmark.isNotEmpty) {
          landmarkController.text = detectedLandmark;
        }
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _locationLoading = false;
        _locationError = e.toString();
      });
    }
  }

  Future<void> _showLookupPicker({
    required String title,
    required List<Map<String, dynamic>> items,
    required String? selectedId,
    required ValueChanged<String> onSelected,
  }) async {
    if (_lookupsLoading) return;

    if (items.isEmpty) {
      return;
    }

    final result = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: const Color(0xFF101424),
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.55,
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close, color: Colors.white70),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1, color: Colors.white12),
                Expanded(
                  child: ListView.separated(
                    itemCount: items.length,
                    separatorBuilder: (_, __) =>
                        const Divider(height: 1, color: Colors.white10),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      final id = item['id']?.toString() ?? '';
                      final name = item['name']?.toString() ?? id;
                      final isSelected = id == selectedId;

                      return ListTile(
                        title: Text(
                          name,
                          style: const TextStyle(color: Colors.white),
                        ),
                        trailing: isSelected
                            ? const Icon(Icons.check,
                                color: Colors.lightBlueAccent)
                            : null,
                        onTap: () => Navigator.pop(context, id),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (result != null && result.isNotEmpty) {
      onSelected(result);
    }
  }

  // ================= OCR =================
  Future<void> _showImageSourceDialog() async {
    final l10n = AppLocalizations.of(context);
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: Text(l10n.tr('addFine.takePhoto')),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: Text(l10n.tr('addFine.chooseFromGallery')),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    final l10n = AppLocalizations.of(context);
    final XFile? pickedFile = await _picker.pickImage(
      source: source,
      imageQuality: 90,
    );

    if (pickedFile == null) return;

    setState(() {
      _selectedImage = File(pickedFile.path);
      _ocrLoading = true;
    });

    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.loginRequired'))),
      );
      setState(() => _ocrLoading = false);
      return;
    }

    try {
      final vehicle =
          await ApiService.readVehicleFromImage(token, _selectedImage!);

      setState(() {
        if (vehicle.plateNumber.isNotEmpty) {
          plateController.text = vehicle.plateNumber;
        }
        if (vehicle.model.isNotEmpty) {
          modelController.text = vehicle.model;
        }
        if (vehicle.color.isNotEmpty) {
          colorController.text = vehicle.color;
        }
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            l10n.tr('addFine.ocrError', params: {'error': '$e'}),
          ),
        ),
      );
    } finally {
      if (mounted) setState(() => _ocrLoading = false);
    }
  }

  // ================= STT (Record) =================
  Future<void> _toggleRecording() async {
    if (_sttLoading) return;

    if (_sttRecording) {
      await _stopRecording();
    } else {
      await _startRecording();
    }
  }

  Future<void> _startRecording() async {
    final l10n = AppLocalizations.of(context);
    final hasPerm = await _recorder.hasPermission();
    if (!hasPerm) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.microphoneDenied'))),
      );
      return;
    }

    final dir = await getTemporaryDirectory();
    if (!mounted) return;
    final filePath = p.join(
      dir.path,
      'stt_${DateTime.now().millisecondsSinceEpoch}.wav',
    );

    setState(() {
      _recordDuration = Duration.zero;
      _recordedPath = null;
      _sttRecording = true;
    });

    _sttTimer?.cancel();
    _sttTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      setState(() => _recordDuration += const Duration(seconds: 1));
    });

    await _recorder.start(
      const RecordConfig(
        encoder: AudioEncoder.wav,
        sampleRate: 16000,
        numChannels: 1,
      ),
      path: filePath,
    );
  }

  Future<void> _stopRecording() async {
    final l10n = AppLocalizations.of(context);
    _sttTimer?.cancel();
    final path = await _recorder.stop();
    if (!mounted) return;

    setState(() {
      _sttRecording = false;
      _recordedPath = path;
    });

    if (path == null || path.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.noRecordedAudio'))),
      );
      return;
    }

    final f = File(path);
    final exists = await f.exists();
    final size = exists ? await f.length() : 0;

    // ignore: avoid_print
    print("🎙️ REC PATH: $path");
    // ignore: avoid_print
    print("🎙️ REC EXISTS: $exists");
    // ignore: avoid_print
    print("🎙️ REC SIZE: $size bytes");

    if (!exists || size < 2000) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.audioTooSmall'))),
      );
      return;
    }

    await _sendAudioToServer(f);
  }

  String _formatDuration(Duration d) {
    String two(int n) => n.toString().padLeft(2, '0');
    return '${two(d.inMinutes)}:${two(d.inSeconds % 60)}';
  }

  // ================= STT (Upload + Poll) =================

  Future<void> _cancelStt() async {
    final l10n = AppLocalizations.of(context);
    final recordedPath = _recordedPath;
    // يوقف التسجيل لو شغال
    if (_sttRecording) {
      _sttTimer?.cancel();
      await _recorder.stop();
    }

    setState(() {
      _sttCancelled = true;
      _sttRecording = false;
      _sttLoading = false;
      _recordedPath = null;
      _recordDuration = Duration.zero;

      // (اختياري) تمسح النص من الترانسكربت
      // transcriptController.clear();
    });

    // (اختياري) حذف ملف الصوت المسجل إن وجد
    try {
      if (recordedPath != null) {
        final f = File(recordedPath);
        if (await f.exists()) await f.delete();
      }
    } catch (_) {}

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(l10n.tr('addFine.sttCancelled'))),
    );
  }

  Future<void> _sendAudioToServer(File audioFile) async {
    final l10n = AppLocalizations.of(context);
    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.loginRequired'))),
      );
      return;
    }

    setState(() {
      _sttLoading = true;
      _sttCancelled = false; // ✅ reset cancel flag لكل عملية جديدة
    });

    try {
      final jobId = await ApiService.requestStt(token, audioFile);
      if (!mounted || _sttCancelled) return;

      if (_sttCancelled) return; // ✅ لو انضغط Cancel بعد الرفع

      final sttResult = await ApiService.pollStt(token, jobId);
      if (!mounted || _sttCancelled) return;

      if (_sttCancelled) return; // ✅ لو انضغط Cancel أثناء polling

      final result = _extractSttResult(sttResult);

      // ✅ transcript parsing
      final transcriptText = _extractTranscriptText(result);

      if (!mounted || _sttCancelled) return;

      transcriptController.text = transcriptText;

      // ✅ apply fields
      if (result is Map) {
        final fieldMap = _extractFieldMap(result['fields']) ??
            _extractFieldMap(result['result']) ??
            _extractFieldMap(result['data']);
        if (fieldMap != null) {
          _applySttFields(fieldMap);
        }
      }

      if (!mounted || _sttCancelled) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.sttSuccess'))),
      );
    } catch (e) {
      if (!mounted || _sttCancelled) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            l10n.tr('addFine.sttError', params: {'error': '$e'}),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _sttLoading = false);
      }
    }
  }

  // ✅✅ أهم تعديل: تعبئة كل الحقول + تصفير dropdown إذا ما في ID
  dynamic _extractSttResult(Map<String, dynamic> sttResult) {
    final result = sttResult['result'];
    if (result != null) {
      return result;
    }

    final data = sttResult['data'];
    if (data is Map && data['result'] != null) {
      return data['result'];
    }

    return data ?? sttResult;
  }

  String _extractTranscriptText(dynamic result) {
    if (result is String) {
      return result.trim();
    }

    if (result is Map) {
      debugPrint('AddViolationPage: STT result map keys=${result.keys.toList()}');
      return _firstNonEmptyLookupValue([
            result['text'],
            result['transcript'],
            result['message'],
            result['result'] is String ? result['result'] : null,
            result['data'] is String ? result['data'] : null,
          ]) ??
          '';
    }

    return '';
  }

  Map<String, dynamic>? _extractFieldMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return null;
  }

  void _applySttFields(Map<String, dynamic> fields) {
    String s(dynamic v) => (v ?? '').toString().trim();

    final plate = s(fields['vehicle_plate']);
    final owner = s(fields['vehicle_owner']);
    final model = s(fields['vehicle_model']);
    final color = s(fields['vehicle_color']);

    final street = s(fields['street_name']);
    final landmark = s(fields['landmark']);
    final description = s(fields['description']);

    final cityId = s(fields['city_id']);
    final cityName = s(fields['city_name']);
    final violationTypeId = s(fields['violation_type_id']);
    final violationTypeName = s(fields['violation_type_name']);

    if (plate.isNotEmpty) plateController.text = plate.toUpperCase();
    if (owner.isNotEmpty) ownerController.text = owner;
    if (model.isNotEmpty) modelController.text = model;
    if (color.isNotEmpty) colorController.text = color;

    if (street.isNotEmpty) streetController.text = street;
    if (landmark.isNotEmpty) landmarkController.text = landmark;
    if (description.isNotEmpty) descriptionController.text = description;
    if (cityName.isNotEmpty) cityNameController.text = cityName;

    // ✅ City dropdown: if no id => null (لا default)
    final resolvedCityId = cityId.isNotEmpty
        ? cityId
        : _matchCityIdFromName(cityName.isNotEmpty ? cityName : null);
    if (resolvedCityId != null && resolvedCityId.isNotEmpty) {
      final ok = cities.any((c) => c['id'].toString() == resolvedCityId);
      if (ok) {
        selectedCityId = resolvedCityId;
      } else {
        debugPrint('AddViolationPage: STT city id not found in lookup: $resolvedCityId');
      }
    } else if (cityName.isNotEmpty) {
      debugPrint('AddViolationPage: STT city mapping failed for "$cityName"');
    }

    // ✅ Violation dropdown: if no id => null (لا default)
    final resolvedViolationTypeId = violationTypeId.isNotEmpty
        ? violationTypeId
        : _matchViolationTypeIdFromName(
            violationTypeName.isNotEmpty ? violationTypeName : null,
          );
    if (resolvedViolationTypeId != null &&
        resolvedViolationTypeId.isNotEmpty) {
      final ok = violationTypes.any(
        (t) => t['id'].toString() == resolvedViolationTypeId,
      );
      if (ok) {
        selectedViolationTypeId = resolvedViolationTypeId;
      } else {
        debugPrint(
          'AddViolationPage: STT violation type id not found in lookup: $resolvedViolationTypeId',
        );
      }
    } else if (violationTypeName.isNotEmpty) {
      debugPrint(
        'AddViolationPage: STT violation type mapping failed for "$violationTypeName"',
      );
    }

    if (mounted) setState(() {});
  }

  Map<String, dynamic>? _extractMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return null;
  }

  Map<String, dynamic>? _extractCreatedViolationPayload(
    Map<String, dynamic> response,
    Map<String, dynamic> requestBody,
  ) {
    dynamic current = response;

    while (true) {
      final map = _extractMap(current);
      if (map == null) break;

      final idValue = map['id'];
      if (idValue != null) {
        final payload = Map<String, dynamic>.from(map);
        final nestedLocation = _extractMap(payload['location']);
        final nestedVehicle = _extractMap(payload['vehicle']);
        final nestedViolationType = _extractMap(payload['violation_type']);

        payload['location'] = nestedLocation ??
            {
              'city_id': requestBody['city_id'],
              'city_name': requestBody['city_name'],
              'street_name': requestBody['street_name'],
              'landmark': requestBody['landmark'],
              'latitude': requestBody['latitude'],
              'longitude': requestBody['longitude'],
            };

        payload['vehicle'] = nestedVehicle ??
            {
              'plate_number': requestBody['vehicle_plate'],
              'owner_name': requestBody['vehicle_owner'],
              'model': requestBody['vehicle_model'],
              'color': requestBody['vehicle_color'],
            };

        payload['violation_type'] = nestedViolationType ??
            {
              'id': requestBody['violation_type_id'],
              'name': _lookupNameById(
                violationTypes,
                requestBody['violation_type_id']?.toString(),
              ),
              'fine_amount': violationTypes
                  .where(
                    (item) =>
                        item['id']?.toString() ==
                        requestBody['violation_type_id']?.toString(),
                  )
                  .map((item) => item['fine_amount'])
                  .firstWhere((_) => true, orElse: () => null),
            };

        payload['vehicle_snapshot'] = payload['vehicle_snapshot'] ??
            {
              'plate_number': requestBody['vehicle_plate'],
              'owner_name': requestBody['vehicle_owner'],
              'model': requestBody['vehicle_model'],
              'color': requestBody['vehicle_color'],
            };

        payload['description'] ??= requestBody['description'];
        payload['occurred_at'] ??= requestBody['occurred_at'];
        payload['fine_amount'] ??= payload['violation_type']?['fine_amount'];
        return payload;
      }

      dynamic next;
      for (final key in const ['data', 'result', 'item', 'violation']) {
        if (map[key] != null) {
          next = map[key];
          break;
        }
      }

      if (next == null || identical(next, current)) break;
      current = next;
    }

    return null;
  }

  // ================= Submit =================
  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    if (!_formKey.currentState!.validate()) return;

    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('addFine.loginRequired'))),
      );
      return;
    }

    setState(() => _loading = true);

    final body = {
      'vehicle_plate': plateController.text.trim().toUpperCase(),
      'vehicle_owner': ownerController.text.trim().isEmpty
          ? null
          : ownerController.text.trim(),
      'vehicle_model': modelController.text.trim().isEmpty
          ? null
          : modelController.text.trim(),
      'vehicle_color': colorController.text.trim().isEmpty
          ? null
          : colorController.text.trim(),
      'city_id': _toIntOrNull(selectedCityId) ?? selectedCityId,
      'city_name': _resolveCityNameForSubmit(),
      'street_name': streetController.text.trim(),
      'landmark': landmarkController.text.trim().isEmpty
          ? null
          : landmarkController.text.trim(),
      'violation_type_id':
          _toIntOrNull(selectedViolationTypeId) ?? selectedViolationTypeId,
      'description': descriptionController.text.trim().isEmpty
          ? null
          : descriptionController.text.trim(),
      'occurred_at': DateTime.now().toIso8601String(),
      'latitude': _latitude,
      'longitude': _longitude,
      'stt_transcript': transcriptController.text.trim().isEmpty
          ? null
          : transcriptController.text.trim(),
    };

// ===== DEBUG BEFORE SUBMIT =====

    try {
      final res = await ApiService.createViolation(token, body);
      final ok = (res['status_code'] == 200) ||
          (res['status'] != null &&
              res['status'].toString().toLowerCase() == 'success');

      if (ok) {
        final payload = _extractCreatedViolationPayload(res, body);
        if (payload != null) {
          final violation = Violation.fromJson(payload);
          if (violation.id > 0) {
            await ViolationPdfService.ensurePdf(violation);
          }
        }

        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(l10n.tr('addFine.createdSuccess'))),
        );
        Navigator.of(context).pop(true);
      } else {
        final msg = res['message'] ?? l10n.tr('addFine.unknownError');
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              l10n.tr('addFine.errorPrefix', params: {'message': '$msg'}),
            ),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            l10n.tr('addFine.requestError', params: {'error': '$e'}),
          ),
        ),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= UI =================
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isRtl = l10n.isRtl;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.tr('addFine.pageTitle'))),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SectionHeader(
                title: l10n.tr('addFine.sectionTitle'),
                subtitle: l10n.tr('addFine.sectionSubtitle'),
              ),
              const SizedBox(height: 16),
              AppButton(
                label: l10n.tr('addFine.addImage'),
                icon: Icons.camera_alt_outlined,
                onPressed: _ocrLoading ? null : _showImageSourceDialog,
                loading: _ocrLoading,
              ),
              const SizedBox(height: 12),
              if (_selectedImage != null) ...[
                ClipRRect(
                  borderRadius: BorderRadius.circular(18),
                  child: Image.file(
                    _selectedImage!,
                    height: 180,
                    width: double.infinity,
                    fit: BoxFit.cover,
                  ),
                ),
                const SizedBox(height: 12),
              ],

              // ===== STT card =====
              AppCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SectionHeader(
                      title: l10n.tr('addFine.sttTitle'),
                      subtitle: l10n.tr('addFine.sttSubtitle'),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: AppButton(
                            label: _sttRecording
                                ? l10n.tr(
                                    'addFine.sttStop',
                                    params: {
                                      'duration':
                                          _formatDuration(_recordDuration),
                                    },
                                  )
                                : l10n.tr('addFine.sttStart'),
                            icon: _sttRecording
                                ? Icons.stop_circle_outlined
                                : Icons.mic_none_outlined,
                            onPressed: _sttLoading ? null : _toggleRecording,
                          ),
                        ),
                        const SizedBox(width: 12),

                        // ✅ Cancel button
                        AppButton(
                          onPressed: (_sttRecording || _sttLoading)
                              ? _cancelStt
                              : null,
                          label: l10n.tr('addFine.sttCancel'),
                          icon: Icons.close,
                          variant: AppButtonVariant.secondary,
                          expanded: false,
                        ),

                        if (_sttLoading)
                          const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: transcriptController,
                      maxLines: 3,
                      textAlign: isRtl ? TextAlign.right : TextAlign.left,
                      textDirection: l10n.textDirection,
                      decoration: InputDecoration(
                        labelText: l10n.tr('addFine.transcript'),
                        prefixIcon: const Icon(Icons.text_snippet_outlined),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      l10n.tr('addFine.sttTip'),
                      style: const TextStyle(fontSize: 12, color: Colors.white70),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 18),
              SectionHeader(
                title: l10n.tr('addFine.vehicleTitle'),
                subtitle: l10n.tr('addFine.vehicleSubtitle'),
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: plateController,
                textCapitalization: TextCapitalization.characters,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.plateNumber'),
                  prefixIcon: const Icon(Icons.directions_car),
                ),
                validator: (v) =>
                    v == null || v.isEmpty ? l10n.tr('addFine.enterPlate') : null,
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: ownerController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.ownerOptional'),
                  prefixIcon: const Icon(Icons.person),
                ),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: modelController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.modelOptional'),
                  prefixIcon: const Icon(Icons.car_rental),
                ),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: colorController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.colorOptional'),
                  prefixIcon: const Icon(Icons.palette),
                ),
              ),
              const SizedBox(height: 16),
              SectionHeader(
                title: l10n.tr('addFine.locationTitle'),
                subtitle: l10n.tr('addFine.locationSubtitle'),
              ),
              const SizedBox(height: 12),

              if (_lookupsLoading)
                const Padding(
                  padding: EdgeInsets.only(bottom: 16),
                  child: LinearProgressIndicator(),
                ),

              if (_lookupsError != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          _lookupsError!,
                          style: const TextStyle(color: PoliceTheme.error),
                        ),
                      ),
                      TextButton(
                        onPressed: _loadLookups,
                        child: Text(l10n.tr('addFine.retry')),
                      ),
                    ],
                  ),
                ),

              Container(
                width: double.infinity,
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(
                          Icons.my_location,
                          color: PoliceTheme.secondary,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _locationLoading
                                ? l10n.tr('addFine.detectingLocation')
                                : _locationError != null
                                    ? l10n.tr('addFine.manualLocation')
                                    : (_latitude != null && _longitude != null)
                                        ? l10n.tr('addFine.locationDetected')
                                        : l10n.tr('addFine.useCurrentLocation'),
                            style: const TextStyle(
                              color: PoliceTheme.textPrimary,
                            ),
                          ),
                        ),
                        TextButton(
                          onPressed:
                              _locationLoading ? null : _detectCurrentLocation,
                          child: Text(
                            _latitude != null
                                ? l10n.tr('addFine.refresh')
                                : l10n.tr('addFine.detect'),
                          ),
                        ),
                      ],
                    ),
                    if (_latitude != null && _longitude != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          'Lat: ${_latitude!.toStringAsFixed(6)}, Lng: ${_longitude!.toStringAsFixed(6)}',
                          style: const TextStyle(
                            color: PoliceTheme.textSecondary,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    if (_locationError != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          l10n.tr('addFine.autoDetectUnavailable'),
                          style: const TextStyle(
                              color: PoliceTheme.warning, fontSize: 12),
                        ),
                      ),
                  ],
                ),
              ),

                           if (cities.isNotEmpty) ...[
                FormField<String>(
                  initialValue: selectedCityId,
                  validator: (_) =>
                      (selectedCityId == null || selectedCityId!.isEmpty) &&
                              cityNameController.text.trim().isEmpty
                          ? l10n.tr('addFine.selectCityOrType')
                          : null,
                  builder: (field) {
                    final selectedName =
                        _lookupNameById(cities, selectedCityId);

                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: _lookupsLoading
                              ? null
                              : () => _showLookupPicker(
                                    title: l10n.tr('addFine.selectTitleCity'),
                                    items: cities,
                                    selectedId: selectedCityId,
                                    onSelected: (value) {
                                      setState(() {
                                        selectedCityId = value;
                                        cityNameController.text =
                                            _lookupNameById(cities, value) ??
                                                '';
                                      });
                                      field.didChange(value);
                                    },
                                  ),
                          child: InputDecorator(
                            decoration: InputDecoration(
                              labelText: l10n.tr('addFine.city'),
                              prefixIcon: const Icon(Icons.location_city),
                              errorText: field.errorText,
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    selectedName ??
                                        l10n.tr('addFine.selectCity'),
                                    textAlign: isRtl
                                        ? TextAlign.right
                                        : TextAlign.left,
                                    style: TextStyle(
                                      color: selectedName == null
                                          ? Colors.grey.shade500
                                          : PoliceTheme.textPrimary,
                                    ),
                                  ),
                                ),
                                const Icon(
                                  Icons.keyboard_arrow_down,
                                  color: PoliceTheme.textSecondary,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 16),
              ] else ...[
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: Text(
                      l10n.tr('addFine.cityListUnavailable'),
                      style: const TextStyle(
                        color: PoliceTheme.warning,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
              ],

              TextFormField(
                controller: cityNameController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.cityName'),
                  hintText: l10n.tr('addFine.cityHint'),
                  prefixIcon: const Icon(Icons.edit_location_alt_outlined),
                ),
                onChanged: (_) => setState(() {}),
                validator: (v) {
                  if ((selectedCityId == null || selectedCityId!.isEmpty) &&
                      (v == null || v.trim().isEmpty)) {
                    return l10n.tr('addFine.enterCity');
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: streetController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.streetName'),
                  hintText: l10n.tr('addFine.streetHint'),
                  prefixIcon: const Icon(Icons.map),
                ),
                validator: (v) => v == null || v.isEmpty
                    ? l10n.tr('addFine.enterStreet')
                    : null,
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: landmarkController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.landmarkOptional'),
                  hintText: l10n.tr('addFine.landmarkHint'),
                  prefixIcon: const Icon(Icons.place_outlined),
                ),
              ),
              const SizedBox(height: 16),

              FormField<String>(
                initialValue: selectedViolationTypeId,
                validator: (_) => violationTypes.isNotEmpty &&
                        (selectedViolationTypeId == null ||
                            selectedViolationTypeId!.isEmpty)
                    ? l10n.tr('addFine.selectViolationTypeError')
                    : null,
                builder: (field) {
                  final selectedName =
                      _lookupNameById(violationTypes, selectedViolationTypeId);

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      InkWell(
                        borderRadius: BorderRadius.circular(14),
                        onTap: _lookupsLoading || violationTypes.isEmpty
                            ? null
                            : () => _showLookupPicker(
                                  title: l10n.tr(
                                    'addFine.selectTitleViolationType',
                                  ),
                                  items: violationTypes,
                                  selectedId: selectedViolationTypeId,
                                  onSelected: (value) {
                                    setState(
                                      () => selectedViolationTypeId = value,
                                    );
                                    field.didChange(value);
                                  },
                                ),
                        child: InputDecorator(
                          decoration: InputDecoration(
                            labelText: l10n.tr('addFine.violationType'),
                            prefixIcon:
                                const Icon(Icons.warning_amber_rounded),
                            errorText: field.errorText,
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  selectedName ??
                                      l10n.tr('addFine.selectViolationType'),
                                  textAlign: isRtl
                                      ? TextAlign.right
                                      : TextAlign.left,
                                  style: TextStyle(
                                    color: selectedName == null
                                        ? Colors.grey.shade500
                                        : PoliceTheme.textPrimary,
                                  ),
                                ),
                              ),
                              const Icon(
                                Icons.keyboard_arrow_down,
                                color: PoliceTheme.textSecondary,
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (violationTypes.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(
                            l10n.tr('addFine.violationTypeListUnavailable'),
                            style: const TextStyle(
                              color: PoliceTheme.warning,
                              fontSize: 12,
                            ),
                          ),
                        ),
                    ],
                  );
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: descriptionController,
                textAlign: isRtl ? TextAlign.right : TextAlign.left,
                textDirection: l10n.textDirection,
                decoration: InputDecoration(
                  labelText: l10n.tr('addFine.descriptionOptional'),
                  hintText: l10n.tr('addFine.descriptionHint'),
                  prefixIcon: const Icon(Icons.notes),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 24),

              AppButton(
                label: l10n.tr('addFine.submit'),
                onPressed: _loading ? null : _submit,
                loading: _loading,
                icon: Icons.check_circle_outline,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
