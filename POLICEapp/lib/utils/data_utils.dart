// lib/utils/date_utils.dart
class AppDateUtils {
  /// Laravel sometimes returns: "2026-01-10 11:39:04"
  /// DateTime.tryParse expects: "2026-01-10T11:39:04"
  static DateTime? parseFlexible(String? raw) {
    if (raw == null) return null;
    final s = raw.trim();
    if (s.isEmpty) return null;

    // Fix "YYYY-MM-DD HH:mm:ss" -> "YYYY-MM-DDTHH:mm:ss"
    final fixed = s.contains(' ') && !s.contains('T') ? s.replaceFirst(' ', 'T') : s;

    return DateTime.tryParse(fixed);
  }

  /// Prefer occurredAt, fallback to createdAt
  static DateTime? violationDate({
    required String? occurredAt,
    required String? createdAt,
  }) {
    return parseFlexible(occurredAt) ?? parseFlexible(createdAt);
  }

  static bool isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }
}
