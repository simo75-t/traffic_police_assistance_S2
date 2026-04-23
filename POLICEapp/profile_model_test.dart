import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/models/profile.dart';

void main() {
  group('Profile model parsing', () {
    test('fromJson converts string booleans and ints correctly', () {
      final profile = Profile.fromJson({
        'id': '42',
        'name': 'أحمد',
        'email': 'test@example.com',
        'phone': null,
        'role': 'officer',
        'is_active': '1',
        'profile_image': '',
        'last_seen_at': '2026-04-20T12:00:00Z',
      });

      expect(profile.id, 42);
      expect(profile.name, 'أحمد');
      expect(profile.email, 'test@example.com');
      expect(profile.phone, isNull);
      expect(profile.role, 'officer');
      expect(profile.isActive, isTrue);
      expect(profile.profileImage, isNull);
      expect(profile.lastSeenAt, '2026-04-20T12:00:00Z');
    });

    test('fromJson defaults missing values safely', () {
      final profile = Profile.fromJson({});

      expect(profile.id, 0);
      expect(profile.name, '');
      expect(profile.email, '');
      expect(profile.role, '');
      expect(profile.isActive, isFalse);
    });
  });
}
