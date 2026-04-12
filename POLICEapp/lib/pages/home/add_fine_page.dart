import 'dart:async';
import 'dart:io';

import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:record/record.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;

import '../../services/api_service.dart';
import '../../services/secure_storage.dart';

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
    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Please login first.')));
      return;
    }

    if (mounted) {
      setState(() {
        _lookupsLoading = true;
        _lookupsError = null;
      });
    }

    try {
      final c = await ApiService.getCities(token);
      final t = await ApiService.getViolationTypes(token);

      setState(() {
        cities = _normalizeLookup(c);
        violationTypes = _normalizeLookup(t);

        // ✅ لا تختاري أول عنصر تلقائيًا (هذا كان سبب أن dropdown يطلع غلط)
        // selectedCityId = cities.isNotEmpty ? cities[0]['id'].toString() : null;
        // selectedViolationTypeId = violationTypes.isNotEmpty ? violationTypes[0]['id'].toString() : null;

        // لو بدك: خليهم null دائمًا حتى يجي STT أو المستخدم يختار
        selectedCityId = null;
        selectedViolationTypeId = null;
        _lookupsLoading = false;
        _lookupsError = null;
      });

      unawaited(_detectCurrentLocation());
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _lookupsLoading = false;
        _lookupsError = 'Error loading lookups: $e';
      });
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(_lookupsError!)));
    }
  }

  List<Map<String, dynamic>> _normalizeLookup(List<dynamic> raw) {
    final out = <Map<String, dynamic>>[];

    for (final item in raw) {
      if (item is Map<String, dynamic>) {
        final id = item['id'];
        final name = item['name'] ?? item['title'] ?? id;
        if (id != null) {
          out.add({'id': id, 'name': name?.toString() ?? ''});
        }
        continue;
      }

      if (item is Map) {
        final map = Map<String, dynamic>.from(item);
        final id = map['id'];
        final name = map['name'] ?? map['title'] ?? id;
        if (id != null) {
          out.add({'id': id, 'name': name?.toString() ?? ''});
        }
        continue;
      }

      if (item is String && item.trim().isNotEmpty) {
        final s = item.trim();
        out.add({'id': s, 'name': s});
      }
    }

    return out;
  }

  int? _toIntOrNull(String? value) {
    if (value == null || value.trim().isEmpty) return null;
    return int.tryParse(value.trim());
  }

  String? _lookupNameById(List<Map<String, dynamic>> items, String? selectedId) {
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

  Future<void> _detectCurrentLocation() async {
    if (_locationLoading) return;

    setState(() {
      _locationLoading = true;
      _locationError = null;
    });

    try {
      var serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        throw Exception('Location service is disabled');
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw Exception('Location permission denied');
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      if (!_isLikelyInSyria(position.latitude, position.longitude)) {
        if (!mounted) return;
        setState(() {
          _locationLoading = false;
          _locationError =
              'Detected device location is outside Syria. Update emulator/device location, then retry.';
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
      ].whereType<String>().map((e) => e.trim()).where((e) => e.isNotEmpty).join(', ');

      final matchedCityId = _matchCityIdFromName(detectedCity);

      setState(() {
        _latitude = position.latitude;
        _longitude = position.longitude;
        _detectedCityName = detectedCity?.trim().isEmpty ?? true
            ? null
            : detectedCity?.trim();
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
                            ? const Icon(Icons.check, color: Colors.lightBlueAccent)
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
              title: const Text('Take photo'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Choose from gallery'),
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
        const SnackBar(content: Text('Please login first.')),
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
        SnackBar(content: Text('OCR error: $e')),
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
    final hasPerm = await _recorder.hasPermission();
    if (!hasPerm) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Microphone permission denied')),
      );
      return;
    }

    final dir = await getTemporaryDirectory();
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
    _sttTimer?.cancel();
    final path = await _recorder.stop();

    setState(() {
      _sttRecording = false;
      _recordedPath = path;
    });

    if (path == null || path.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No recorded audio found')),
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
        const SnackBar(content: Text('Recorded audio is empty / too small')),
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
      if (_recordedPath != null) {
        final f = File(_recordedPath!);
        if (await f.exists()) await f.delete();
      }
    } catch (_) {}

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('STT cancelled')),
    );
  }

  Future<void> _sendAudioToServer(File audioFile) async {
    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Please login first.')));
      return;
    }

    setState(() {
      _sttLoading = true;
      _sttCancelled = false; // ✅ reset cancel flag لكل عملية جديدة
    });

    try {
      final jobId = await ApiService.requestStt(token, audioFile);

      if (_sttCancelled) return; // ✅ لو انضغط Cancel بعد الرفع

      final sttResult = await ApiService.pollStt(token, jobId);

      if (_sttCancelled) return; // ✅ لو انضغط Cancel أثناء polling

      final result = sttResult['result'];

      // ✅ transcript parsing
      String transcriptText = '';
      if (result is Map) {
        transcriptText =
            (result['text'] ?? result['transcript'] ?? '').toString();
      } else if (result is String) {
        transcriptText = result;
      }

      if (!mounted || _sttCancelled) return;

      setState(() {
        transcriptController.text = transcriptText;
      });

      // ✅ apply fields
      if (result is Map && result['fields'] is Map) {
        _applySttFields(Map<String, dynamic>.from(result['fields']));
      }

      if (!mounted || _sttCancelled) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('STT success ✅')),
      );
    } catch (e) {
      if (!mounted || _sttCancelled) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('STT error: $e')));
    } finally {
      if (mounted) {
        setState(() => _sttLoading = false);
      }
    }
  }

  // ✅✅ أهم تعديل: تعبئة كل الحقول + تصفير dropdown إذا ما في ID
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

    if (plate.isNotEmpty) plateController.text = plate.toUpperCase();
    if (owner.isNotEmpty) ownerController.text = owner;
    if (model.isNotEmpty) modelController.text = model;
    if (color.isNotEmpty) colorController.text = color;

    if (street.isNotEmpty) streetController.text = street;
    if (landmark.isNotEmpty) landmarkController.text = landmark;
    if (description.isNotEmpty) descriptionController.text = description;
    if (cityName.isNotEmpty) cityNameController.text = cityName;

    // ✅ City dropdown: if no id => null (لا default)
    if (cityId.isNotEmpty) {
      final ok = cities.any((c) => c['id'].toString() == cityId);
      selectedCityId = ok ? cityId : null;
    } else {
      selectedCityId = null;
    }

    // ✅ Violation dropdown: if no id => null (لا default)
    if (violationTypeId.isNotEmpty) {
      final ok =
          violationTypes.any((t) => t['id'].toString() == violationTypeId);
      selectedViolationTypeId = ok ? violationTypeId : null;
    } else {
      selectedViolationTypeId = null;
    }

    if (mounted) setState(() {});
  }

  // ================= Submit =================
  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final token = await SecureStorage.readToken();
    if (token == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Please login first.')));
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
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Violation created successfully')),
        );
        Navigator.of(context).pop(true);
      } else {
        final msg = res['message'] ?? 'Unknown error';
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $msg')),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Request error: $e')),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= UI =================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Register Violation')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              ElevatedButton.icon(
                icon: const Icon(Icons.camera),
                label: _ocrLoading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Add Car Image (OCR)'),
                onPressed: _ocrLoading ? null : _showImageSourceDialog,
              ),
              const SizedBox(height: 12),

              // ===== STT card =====
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.06),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white12),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Voice input (STT)',
                      style:
                          TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            icon: Icon(_sttRecording ? Icons.stop : Icons.mic),
                            label: Text(
                              _sttRecording
                                  ? 'Stop (${_formatDuration(_recordDuration)})'
                                  : 'Start Recording',
                            ),
                            onPressed: _sttLoading ? null : _toggleRecording,
                          ),
                        ),
                        const SizedBox(width: 12),

                        // ✅ Cancel button
                        IconButton(
                          onPressed: (_sttRecording || _sttLoading)
                              ? _cancelStt
                              : null,
                          icon: const Icon(Icons.close),
                          tooltip: 'Cancel',
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
                      decoration: const InputDecoration(
                        labelText: 'Transcript (from STT)',
                        prefixIcon: Icon(Icons.text_snippet_outlined),
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Tip: After STT, fields may auto-fill if the backend returns structured data.',
                      style: TextStyle(fontSize: 12, color: Colors.white70),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 18),

              TextFormField(
                controller: plateController,
                textCapitalization: TextCapitalization.characters,
                decoration: const InputDecoration(
                  labelText: 'Plate Number',
                  prefixIcon: Icon(Icons.directions_car),
                ),
                validator: (v) =>
                    v == null || v.isEmpty ? 'Enter plate number' : null,
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: ownerController,
                decoration: const InputDecoration(
                  labelText: 'Owner Name (optional)',
                  prefixIcon: Icon(Icons.person),
                ),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: modelController,
                decoration: const InputDecoration(
                  labelText: 'Car Model (optional)',
                  prefixIcon: Icon(Icons.car_rental),
                ),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: colorController,
                decoration: const InputDecoration(
                  labelText: 'Car Color (optional)',
                  prefixIcon: Icon(Icons.palette),
                ),
              ),
              const SizedBox(height: 16),

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
                          style: const TextStyle(color: Colors.redAccent),
                        ),
                      ),
                      TextButton(
                        onPressed: _loadLookups,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                ),

              Container(
                width: double.infinity,
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white12),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.my_location, color: Colors.white70),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _locationLoading
                                ? 'Detecting current location...'
                                : _locationError != null
                                    ? 'Manual location entry'
                                    : (_latitude != null && _longitude != null)
                                        ? 'Location detected'
                                        : 'Use current location to fill city automatically',
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                        TextButton(
                          onPressed: _locationLoading ? null : _detectCurrentLocation,
                          child: Text(_latitude != null ? 'Refresh' : 'Detect'),
                        ),
                      ],
                    ),
                    if (_latitude != null && _longitude != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          'Lat: ${_latitude!.toStringAsFixed(6)}, Lng: ${_longitude!.toStringAsFixed(6)}',
                          style: const TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ),
                    if (_locationError != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          'Auto-detect is unavailable on this emulator right now. You can type the city, street, and landmark manually.',
                          style: const TextStyle(color: Colors.orangeAccent, fontSize: 12),
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
                      ? 'Select a city or type one manually'
                      : null,
                  builder: (field) {
                    final selectedName = _lookupNameById(cities, selectedCityId);

                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: _lookupsLoading
                              ? null
                              : () => _showLookupPicker(
                                    title: 'Select City',
                                    items: cities,
                                    selectedId: selectedCityId,
                                    onSelected: (value) {
                                      setState(() {
                                        selectedCityId = value;
                                        cityNameController.text =
                                            _lookupNameById(cities, value) ?? '';
                                      });
                                      field.didChange(value);
                                    },
                                  ),
                          child: InputDecorator(
                            decoration: InputDecoration(
                              labelText: 'City',
                              prefixIcon: const Icon(Icons.location_city),
                              errorText: field.errorText,
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    selectedName ?? 'Select a city',
                                    style: TextStyle(
                                      color: selectedName == null
                                          ? Colors.white54
                                          : Colors.white,
                                    ),
                                  ),
                                ),
                                const Icon(
                                  Icons.keyboard_arrow_down,
                                  color: Colors.white70,
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
                    alignment: Alignment.centerLeft,
                    child: Text(
                      'City list is not loaded from the server yet. Type the city name manually below.',
                      style: const TextStyle(
                        color: Colors.orangeAccent,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
              ],

              TextFormField(
                controller: cityNameController,
                textDirection: TextDirection.rtl,
                decoration: const InputDecoration(
                  labelText: 'City Name',
                  hintText: 'مثال: دمشق',
                  prefixIcon: Icon(Icons.edit_location_alt_outlined),
                ),
                onChanged: (_) => setState(() {}),
                validator: (v) {
                  if ((selectedCityId == null || selectedCityId!.isEmpty) &&
                      (v == null || v.trim().isEmpty)) {
                    return 'Enter the city name';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: streetController,
                textDirection: TextDirection.rtl,
                decoration: const InputDecoration(
                  labelText: 'Street Name',
                  hintText: 'مثال: شارع 29 أيار',
                  prefixIcon: Icon(Icons.map),
                ),
                validator: (v) =>
                    v == null || v.isEmpty ? 'Enter street name' : null,
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: landmarkController,
                textDirection: TextDirection.rtl,
                decoration: const InputDecoration(
                  labelText: 'Landmark (optional)',
                  hintText: 'مثال: قرب مبنى البريد',
                  prefixIcon: Icon(Icons.place_outlined),
                ),
              ),
              const SizedBox(height: 16),

              FormField<String>(
                initialValue: selectedViolationTypeId,
                validator: (_) =>
                    violationTypes.isNotEmpty &&
                            (selectedViolationTypeId == null ||
                                selectedViolationTypeId!.isEmpty)
                        ? 'Select violation type'
                        : null,
                builder: (field) {
                  final selectedName =
                      _lookupNameById(violationTypes, selectedViolationTypeId);

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      InkWell(
                        borderRadius: BorderRadius.circular(14),
                        onTap: _lookupsLoading
                            ? null
                            : () => _showLookupPicker(
                                  title: 'Select Violation Type',
                                  items: violationTypes,
                                  selectedId: selectedViolationTypeId,
                                  onSelected: (value) {
                                    setState(
                                        () => selectedViolationTypeId = value);
                                    field.didChange(value);
                                  },
                                ),
                        child: InputDecorator(
                          decoration: InputDecoration(
                            labelText: 'Violation Type',
                            prefixIcon:
                                const Icon(Icons.warning_amber_rounded),
                            errorText: field.errorText,
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  selectedName ?? 'Select violation type',
                                  style: TextStyle(
                                    color: selectedName == null
                                        ? Colors.white54
                                        : Colors.white,
                                  ),
                                ),
                              ),
                              const Icon(
                                Icons.keyboard_arrow_down,
                                color: Colors.white70,
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

              TextFormField(
                controller: descriptionController,
                textDirection: TextDirection.rtl,
                decoration: const InputDecoration(
                  labelText: 'Description (optional)',
                  hintText: 'اكتبي الوصف بالعربي أو الإنجليزي',
                  prefixIcon: Icon(Icons.notes),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Submit'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
