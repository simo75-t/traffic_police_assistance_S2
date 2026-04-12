import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  static const String _tokenKey = 'auth_token';
  static const String _tokenTypeKey = 'auth_token_type';

  static Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  static Future<void> saveTokenType(String tokenType) async {
    await _storage.write(key: _tokenTypeKey, value: tokenType);
  }

  static Future<void> saveAuthSession({
    required String token,
    String tokenType = 'Bearer',
  }) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _tokenTypeKey, value: tokenType);
  }

  static Future<String?> readToken() async {
    return _storage.read(key: _tokenKey);
  }

  static Future<String> readTokenType() async {
    final value = await _storage.read(key: _tokenTypeKey);
    if (value == null || value.trim().isEmpty) {
      return 'Bearer';
    }
    return value;
  }

  static Future<String?> readAuthorizationHeader() async {
    final token = await readToken();
    if (token == null || token.trim().isEmpty) {
      return null;
    }
    final tokenType = await readTokenType();
    return '$tokenType $token';
  }

  static Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _tokenTypeKey);
  }

}
