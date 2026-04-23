import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/services/api_service.dart';

void main() {
  group('AppApiException', () {
    test('toString shows message only when no errors exist', () {
      const exception = AppApiException(
        statusCode: 422,
        message: 'فشل التحقق',
      );

      expect(exception.toString(), 'فشل التحقق');
    });

    test('toString includes error details when errors are present', () {
      const exception = AppApiException(
        statusCode: 422,
        message: 'فشل التحقق',
        errors: {
          'email': ['حقل البريد مطلوب']
        },
      );

      expect(exception.toString(), contains('حقل البريد مطلوب'));
    });
  });
}
