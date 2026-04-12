class VehicleOcrResult {
  final String plateNumber;
  final String model;
  final String color;

  VehicleOcrResult({
    required this.plateNumber,
    required this.model,
    required this.color,
  });

  factory VehicleOcrResult.fromJson(Map<String, dynamic> json) {
    return VehicleOcrResult(
      plateNumber: (json['plate_number'] ?? json['plate'] ?? '').toString(),
      model: (json['model'] ?? '').toString(),
      color: (json['color'] ?? '').toString(),
    );
  }

  static VehicleOcrResult empty() => VehicleOcrResult(
        plateNumber: '',
        model: '',
        color: '',
      );
}
