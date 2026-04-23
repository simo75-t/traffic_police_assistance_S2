import 'package:flutter_test/flutter_test.dart';
import 'package:police_traffic_assistant/services/api_service.dart';

void main() {
  group('ApiService login helper utilities', () {
    test('extractLoginToken returns nested token when available', () {
      final response = {
        'data': {'token': 'nested-token-value'},
      };

      expect(ApiService.extractLoginToken(response), 'nested-token-value');
    });

    test('extractLoginToken prefers top-level token values', () {
      final response = {
        'token': 'top-level-token',
        'data': {'token': 'nested-token'},
      };

      expect(ApiService.extractLoginToken(response), 'top-level-token');
    });

    test('extractLoginTokenType returns Bearer by default', () {
      final response = <String, dynamic>{};
      expect(ApiService.extractLoginTokenType(response), 'Bearer');
    });

    test('extractLoginTokenType returns token_type from nested data', () {
      final response = {
        'data': {'token_type': 'Token'},
      };
      expect(ApiService.extractLoginTokenType(response), 'Token');
    });
  });
}
