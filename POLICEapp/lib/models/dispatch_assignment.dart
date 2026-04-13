class DispatchAssignment {
  final int assignmentId;
  final int reportId;
  final String title;
  final String description;
  final String status;
  final String priority;
  final double? distanceKm;
  final String? assignedAt;
  final String? responseDeadline;
  final String? imageUrl;
  final String? reporterName;
  final String? reporterPhone;
  final String? address;
  final String? streetName;
  final String? landmark;
  final String? city;
  final double? latitude;
  final double? longitude;

  const DispatchAssignment({
    required this.assignmentId,
    required this.reportId,
    required this.title,
    required this.description,
    required this.status,
    required this.priority,
    this.distanceKm,
    this.assignedAt,
    this.responseDeadline,
    this.imageUrl,
    this.reporterName,
    this.reporterPhone,
    this.address,
    this.streetName,
    this.landmark,
    this.city,
    this.latitude,
    this.longitude,
  });

  factory DispatchAssignment.fromJson(Map<String, dynamic> json) {
    final report = (json['report'] as Map?)?.cast<String, dynamic>() ?? const {};
    final location = (report['location'] as Map?)?.cast<String, dynamic>() ?? const {};
    final reporter = (report['reporter'] as Map?)?.cast<String, dynamic>() ?? const {};

    return DispatchAssignment(
      assignmentId: _toInt(json['assignment_id']),
      reportId: _toInt(report['id']),
      title: (report['title'] ?? '').toString(),
      description: (report['description'] ?? '').toString(),
      status: (report['status'] ?? json['assignment_status'] ?? '').toString(),
      priority: (report['priority'] ?? '').toString(),
      distanceKm: _toDouble(json['distance_km']),
      assignedAt: json['assigned_at']?.toString(),
      responseDeadline: json['response_deadline']?.toString(),
      imageUrl: report['image_url']?.toString(),
      reporterName: reporter['name']?.toString(),
      reporterPhone: reporter['phone']?.toString(),
      address: location['address']?.toString(),
      streetName: location['street_name']?.toString(),
      landmark: location['landmark']?.toString(),
      city: location['city']?.toString(),
      latitude: _toDouble(location['latitude']),
      longitude: _toDouble(location['longitude']),
    );
  }

  static int _toInt(dynamic value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  static double? _toDouble(dynamic value) {
    if (value is double) return value;
    if (value is int) return value.toDouble();
    return double.tryParse(value?.toString() ?? '');
  }
}
