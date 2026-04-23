class Profile {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String role;
  final bool isActive;
  final String? profileImage;
  final String? lastSeenAt;

  const Profile({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.isActive,
    this.phone,
    this.profileImage,
    this.lastSeenAt,
  });

  factory Profile.fromJson(Map<String, dynamic> json) {
    return Profile(
      id: _toInt(json['id']),
      name: (json['name'] ?? '').toString().trim(),
      email: (json['email'] ?? '').toString().trim(),
      phone: _nullableString(json['phone']),
      role: (json['role'] ?? '').toString().trim(),
      isActive: _toBool(json['is_active']),
      profileImage: _nullableString(json['profile_image']),
      lastSeenAt: _nullableString(json['last_seen_at']),
    );
  }

  static int _toInt(dynamic value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  static bool _toBool(dynamic value) {
    if (value is bool) return value;
    if (value is num) return value != 0;

    final normalized = value?.toString().trim().toLowerCase();
    return normalized == 'true' || normalized == '1';
  }

  static String? _nullableString(dynamic value) {
    final text = value?.toString().trim();
    if (text == null || text.isEmpty) {
      return null;
    }
    return text;
  }
}
