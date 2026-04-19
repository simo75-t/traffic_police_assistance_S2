import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/models/dispatch_assignment.dart';

void main() {
  test('DispatchAssignment.fromJson parses fields correctly', () {
    final json = {
      'assignment_id': 10,
      'distance_km': 2.5,
      'assigned_at': '2026-04-17T18:00:00Z',
      'response_deadline': null,
      'assignment_status': 'assigned',
      'report': {
        'id': 42,
        'title': 'اختبار بلاغ',
        'description': 'تفاصيل البلاغ',
        'status': 'dispatched',
        'priority': 'high',
        'image_url': 'https://example.com/image.jpg',
        'location': {
          'address': 'شارع الاختبار',
          'street_name': 'شارع',
          'landmark': 'قرب المدخل',
          'city': 'المدينة',
          'latitude': 25.0,
          'longitude': 44.0
        },
        'reporter': {'name': 'أحمد', 'phone': '0500000000'}
      }
    };

    final a = DispatchAssignment.fromJson(json);
    expect(a.assignmentId, 10);
    expect(a.reportId, 42);
    expect(a.title, 'اختبار بلاغ');
    expect(a.priority, 'high');
    expect(a.imageUrl, 'https://example.com/image.jpg');
    expect(a.latitude, 25.0);
  });
}
