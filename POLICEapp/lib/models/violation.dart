import 'dart:convert';

class Violation {
  final int id;
  final Map<String, dynamic>? vehicle;
  final Map<String, dynamic>? location;
  final Map<String, dynamic>? violationType;
  final Map<String, dynamic>? vehicleSnapshot;
  final Map<String, dynamic>? plateSnapshot;
  final Map<String, dynamic>? ownerSnapshot;
  final String? description;
  final String occurredAt;
  final String? createdAt;
  final dynamic fineAmount;
  final int? sourceReportId;
  final String? dataSource;
  final bool? isSynthetic;
  final String? severityLevel;
  final String? status;
  final String? pdfPath;
  final String? pdfUrl;

  Violation({
    required this.id,
    required this.vehicle,
    required this.location,
    required this.violationType,
    required this.vehicleSnapshot,
    required this.plateSnapshot,
    required this.ownerSnapshot,
    required this.description,
    required this.occurredAt,
    this.createdAt,
    this.fineAmount,
    this.sourceReportId,
    this.dataSource,
    this.isSynthetic,
    this.severityLevel,
    this.status,
    this.pdfPath,
    this.pdfUrl,
  });

  factory Violation.fromJson(Map<String, dynamic> json) {
    final rawVehicle = _asMap(json['vehicle']);
    final rawLocation =
        _asMap(json['location']) ?? _asMap(json['violation_location']);
    final rawViolationType =
        _asMap(json['violation_type']) ?? _asMap(json['violationType']);
    final rawOwnerSnapshot = _normalizeSnapshot(json['owner_snapshot']) ??
        _snapshotFromOwner(rawVehicle);
    final rawVehicleSnapshot = _normalizeSnapshot(json['vehicle_snapshot']) ??
        _snapshotFromVehicle(rawVehicle);

    return Violation(
      id: json['id'] ?? 0,
      vehicle: rawVehicle,
      location: rawLocation,
      violationType: rawViolationType,
      vehicleSnapshot: rawVehicleSnapshot,
      plateSnapshot: _normalizeSnapshot(json['plate_snapshot']),
      ownerSnapshot: rawOwnerSnapshot,
      description: json['description']?.toString(),
      occurredAt: json['occurred_at']?.toString() ?? '',
      createdAt: json['created_at']?.toString(),
      fineAmount: json['fine_amount'] ?? rawViolationType?['fine_amount'],
      sourceReportId: _asInt(json['source_report_id']),
      dataSource: json['data_source']?.toString(),
      isSynthetic: _asBool(json['is_synthetic']),
      severityLevel: json['severity_level']?.toString(),
      status: json['status']?.toString(),
      pdfPath: json['pdf_path']?.toString(),
      pdfUrl: json['pdf_url']?.toString(),
    );
  }

  Map<String, dynamic>? get locationCity => _asMap(location?['city']);

  int? get locationCityId {
    return _asInt(location?['city_id']) ?? _asInt(locationCity?['id']);
  }

  String? get locationCityName {
    return _firstNonEmpty([
      locationCity?['name']?.toString(),
      location?['city_name']?.toString(),
      location?['city']?.toString(),
    ]);
  }

  String? get locationStreetName => location?['street_name']?.toString();
  String? get locationLandmark => location?['landmark']?.toString();
  String? get locationAddress => location?['address']?.toString();
  double? get locationLatitude => _asDouble(location?['latitude']);
  double? get locationLongitude => _asDouble(location?['longitude']);
  String? get ownerName {
    final nestedOwner = _asMap(vehicle?['owner']);
    return _firstNonEmpty([
      ownerSnapshot?['owner_name']?.toString(),
      ownerSnapshot?['name']?.toString(),
      ownerSnapshot?['full_name']?.toString(),
      ownerSnapshot?['owner']?.toString(),
      vehicleSnapshot?['owner_name']?.toString(),
      vehicleSnapshot?['name']?.toString(),
      vehicle?['owner_name']?.toString(),
      vehicle?['owner']?.toString(),
      nestedOwner?['name']?.toString(),
      nestedOwner?['full_name']?.toString(),
      nestedOwner?['owner_name']?.toString(),
    ]);
  }

  String? get plateNumber {
    return _firstNonEmpty([
      plateSnapshot?['plate_number']?.toString(),
      vehicleSnapshot?['plate_number']?.toString(),
      vehicle?['plate_number']?.toString(),
    ]);
  }

  String? get vehicleModelName {
    return _firstNonEmpty([
      vehicleSnapshot?['model']?.toString(),
      vehicle?['model']?.toString(),
      vehicle?['vehicle_model']?.toString(),
    ]);
  }

  String? get vehicleColorName {
    return _firstNonEmpty([
      vehicleSnapshot?['color']?.toString(),
      vehicle?['color']?.toString(),
      vehicle?['vehicle_color']?.toString(),
    ]);
  }

  static Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    if (value is String) {
      final text = value.trim();
      if (text.isEmpty) return null;
      if (text.startsWith('{') || text.startsWith('[')) {
        try {
          final decoded = jsonDecode(text);
          if (decoded is Map<String, dynamic>) return decoded;
          if (decoded is Map) return Map<String, dynamic>.from(decoded);
        } catch (_) {
          return null;
        }
      }
    }
    return null;
  }

  static Map<String, dynamic>? _normalizeSnapshot(dynamic value) {
    final mapValue = _asMap(value);
    if (mapValue != null) return mapValue;

    if (value is String) {
      final text = value.trim();
      if (text.isEmpty) return null;

      if (text.startsWith('{') || text.startsWith('[')) {
        try {
          final decoded = jsonDecode(text);
          final decodedMap = _asMap(decoded);
          if (decodedMap != null) return decodedMap;
        } catch (_) {
          // Fall back to storing the raw string below.
        }
      }

      return {'path': text};
    }

    return null;
  }

  static Map<String, dynamic>? _snapshotFromVehicle(
      Map<String, dynamic>? vehicle) {
    if (vehicle == null) return null;

    final plate = vehicle['plate_number']?.toString().trim();
    final owner = vehicle['owner_name']?.toString().trim();
    final snapshot = <String, dynamic>{};

    if (plate != null && plate.isNotEmpty) {
      snapshot['plate_number'] = plate;
    }
    if (owner != null && owner.isNotEmpty) {
      snapshot['owner_name'] = owner;
    }

    return snapshot.isEmpty ? null : snapshot;
  }

  static Map<String, dynamic>? _snapshotFromOwner(
      Map<String, dynamic>? vehicle) {
    if (vehicle == null) return null;

    final nestedOwner = _asMap(vehicle['owner']);
    final ownerName = _firstNonEmpty([
      vehicle['owner_name']?.toString(),
      vehicle['owner'] is String ? vehicle['owner']?.toString() : null,
      nestedOwner?['name']?.toString(),
      nestedOwner?['full_name']?.toString(),
      nestedOwner?['owner_name']?.toString(),
    ]);

    if (ownerName == null || ownerName.isEmpty) return null;
    return {'owner_name': ownerName};
  }

  static int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value == null) return null;
    return int.tryParse(value.toString());
  }

  static double? _asDouble(dynamic value) {
    if (value is double) return value;
    if (value is num) return value.toDouble();
    if (value == null) return null;
    return double.tryParse(value.toString());
  }

  static bool? _asBool(dynamic value) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value == null) return null;
    final text = value.toString().toLowerCase();
    if (text == 'true' || text == '1') return true;
    if (text == 'false' || text == '0') return false;
    return null;
  }

  static String? _firstNonEmpty(List<String?> values) {
    for (final value in values) {
      if (value != null && value.trim().isNotEmpty) {
        return value;
      }
    }
    return null;
  }
}
