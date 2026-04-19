import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/models/violation.dart';

void main() {
  test('Violation.fromJson parses fields correctly', () {
    final json = {
      'id': 99,
      'description': 'خرق السرعة',
      'occurred_at': '2026-04-17 15:10:00',
      'created_at': '2026-04-17T15:11:00Z',
      'fine_amount': 100,
      'source_report_id': 55,
      'location': {
        'city': {'id': 3, 'name': 'المدينة'},
        'street_name': 'شارع الزهر'
      }
    };

    final v = Violation.fromJson(json);
    expect(v.id, 99);
    expect(v.description, 'خرق السرعة');
    expect(v.fineAmount, 100);
    expect(v.sourceReportId, 55);
    expect(v.locationStreetName, 'شارع الزهر');
  });
}
