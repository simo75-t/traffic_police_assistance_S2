import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/utils/data_utils.dart';

void main() {
  group('AppDateUtils', () {
    test('parseFlexible handles Laravel and ISO timestamps', () {
      expect(AppDateUtils.parseFlexible('2026-04-17 15:10:00'),
          DateTime.parse('2026-04-17T15:10:00'));
      expect(AppDateUtils.parseFlexible('2026-04-17T15:10:00'),
          DateTime.parse('2026-04-17T15:10:00'));
      expect(AppDateUtils.parseFlexible(null), isNull);
      expect(AppDateUtils.parseFlexible(''), isNull);
    });

    test('violationDate prefers occurredAt over createdAt', () {
      final date = AppDateUtils.violationDate(
        occurredAt: '2026-04-17 15:10:00',
        createdAt: '2026-04-18 10:00:00',
      );
      expect(date, DateTime.parse('2026-04-17T15:10:00'));
    });

    test('violationDate falls back to createdAt when occurredAt is invalid',
        () {
      final date = AppDateUtils.violationDate(
        occurredAt: null,
        createdAt: '2026-04-18 10:00:00',
      );
      expect(date, DateTime.parse('2026-04-18T10:00:00'));
    });

    test('isSameDay compares just the date portion', () {
      final a = DateTime.parse('2026-04-17T08:00:00');
      final b = DateTime.parse('2026-04-17T23:59:59');
      final c = DateTime.parse('2026-04-18T00:00:00');
      expect(AppDateUtils.isSameDay(a, b), isTrue);
      expect(AppDateUtils.isSameDay(a, c), isFalse);
    });
  });
}
