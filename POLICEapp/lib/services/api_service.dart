import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../config.dart';
import '../models/dispatch_assignment.dart';
import '../models/profile.dart';
import '../models/vehicle_ocr_result.dart';
import '../models/violation.dart';

class ApiService {
  static const Duration _defaultTimeout = Duration(seconds: 20);
  static const int _maxAttempts = 3;

  static Future<Map<String, dynamic>> login(
      String email, String password) async {
    final res = await _sendJson(
      method: 'POST',
      path: '/login',
      body: {
        'email': email,
        'password': password,
      },
      requiresAuth: false,
    );

    final decoded = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    return _normalizeMessage(decoded);
  }

  static String? extractLoginToken(Map<String, dynamic> response) {
    return _firstNonEmptyString([
      response['token'],
      response['access_token'],
      _asMap(response['data'])?['token'],
      _asMap(response['data'])?['access_token'],
    ]);
  }

  static String extractLoginTokenType(Map<String, dynamic> response) {
    return _firstNonEmptyString([
          response['token_type'],
          _asMap(response['data'])?['token_type'],
        ]) ??
        'Bearer';
  }

  static Future<Profile> getProfile(String token) async {
    final res = await _sendJson(
      method: 'GET',
      path: '/profile',
      token: token,
    );

    final body = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final profileData = _asMap(_extractData(body)) ?? const <String, dynamic>{};

    return Profile.fromJson({
      'id': profileData['id'] ?? 0,
      'name': profileData['name'] ?? '',
      'email': profileData['email'] ?? '',
      'phone': profileData['phone'],
      'role': profileData['role'] ?? '',
      'is_active': profileData['is_active'] ?? false,
      'profile_image': profileData['profile_image'],
      'last_seen_at': profileData['last_seen_at'],
    });
  }

  static Future<Profile> updateProfile(
    String token, {
    required String name,
    required String email,
    String? phone,
  }) async {
    final res = await _sendJson(
      method: 'POST',
      path: '/profile/update',
      token: token,
      body: {
        'name': name.trim(),
        'email': email.trim(),
        'phone': phone == null || phone.trim().isEmpty ? null : phone.trim(),
      },
    );

    final body = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final profileData = _asMap(_extractData(body)) ?? const <String, dynamic>{};
    return Profile.fromJson(profileData);
  }

  static Future<List<Violation>> getViolations(String token) async {
    final res = await _sendJson(
      method: 'GET',
      path: '/violations',
      token: token,
    );

    final decoded =
        _decodeMapOrListOrThrow(res.body, statusCode: res.statusCode);
    final listJson = _extractList(decoded);
    final result = <Violation>[];

    for (final item in listJson) {
      final mapItem = _toMapItem(item);
      if (mapItem == null) continue;
      result.add(Violation.fromJson(mapItem));
    }

    return result;
  }

  static Future<Map<String, dynamic>> createViolation(
    String token,
    Map<String, dynamic> data,
  ) async {
    final res = await _sendJson(
      method: 'POST',
      path: '/create',
      token: token,
      body: data,
      timeout: const Duration(seconds: 30),
    );

    return _normalizeMessage(
      _decodeMapOrThrow(res.body, statusCode: res.statusCode),
    );
  }

  static Future<bool> logout(String token) async {
    try {
      await _sendJson(method: 'POST', path: '/logout', token: token);
      return true;
    } on AppApiException catch (e) {
      if (e.statusCode == 401) {
        return true;
      }
      return false;
    }
  }

  static Future<void> updateFcmToken(String token, String fcmToken) async {
    await _sendJson(
      method: 'POST',
      path: '/fcm-token',
      token: token,
      body: {
        'fcm_token': fcmToken,
      },
    );
  }

  static Future<http.Response> updateOfficerLiveLocation(
    String token, {
    required double latitude,
    required double longitude,
    String? availabilityStatus,
  }) async {
    return await _sendJson(
      method: 'POST',
      path: '/officers/live-location',
      token: token,
      body: {
        'latitude': latitude,
        'longitude': longitude,
        if (availabilityStatus != null && availabilityStatus.isNotEmpty)
          'availability_status': availabilityStatus,
      },
    );
  }

  static Future<List<DispatchAssignment>> getDispatchAssignments(
      String token) async {
    final res = await _sendJson(
      method: 'GET',
      path: '/officers/assignments',
      token: token,
    );

    final decoded = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final listJson = _extractList(decoded);

    return listJson
        .map((item) => _toMapItem(item))
        .whereType<Map<String, dynamic>>()
        .map(DispatchAssignment.fromJson)
        .toList();
  }

  /// Mark an assignment/report as processed/completed by the assigned officer.
  /// This replaces the accept/reject flow for nearest-officer assignments.
  static Future<void> startReportProcessing(
    String token, {
    required int assignmentId,
    String? notes,
  }) async {
    await _sendJson(
      method: 'POST',
      path: '/officers/assignments/$assignmentId/start',
      token: token,
      body: {
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );
  }

  static Future<void> completeReport(
    String token, {
    required int assignmentId,
    String? notes,
  }) async {
    await _sendJson(
      method: 'POST',
      path: '/officers/assignments/$assignmentId/complete',
      token: token,
      body: {
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );
  }

  static Future<List<dynamic>> getCities(String token) async {
    return _getLookupList(token: token, path: '/cities', entity: 'cities');
  }

  static Future<List<dynamic>> getViolationTypes(String token) async {
    return _getLookupList(
      token: token,
      path: '/violation-types',
      entity: 'violation types',
    );
  }

  static Future<List<dynamic>> getAiCities(String token) async {
    return _getLookupList(
        token: token, path: '/ai_cities', entity: 'AI cities');
  }

  static Future<List<dynamic>> getAiViolationTypes(String token) async {
    return _getLookupList(
      token: token,
      path: '/ai_violation-types',
      entity: 'AI violation types',
    );
  }

  static Future<http.Response> createViolationResponse(
    String token,
    Map<String, dynamic> data,
  ) {
    return _sendJson(
      method: 'POST',
      path: '/create',
      token: token,
      body: data,
      timeout: const Duration(seconds: 30),
    );
  }

  static Future<String> requestPlateOcr(String token, File imageFile) async {
    final streamed = await _sendMultipart(
      path: '/ocr/plate',
      token: token,
      filePaths: [
        _MultipartFilePath(field: 'image', path: imageFile.path),
      ],
      timeout: const Duration(seconds: 60),
    );

    final res = await http.Response.fromStream(streamed);
    final decoded = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final jobId = _extractJobId(decoded);

    if (jobId == null || jobId.isEmpty) {
      throw AppApiException(
        statusCode: res.statusCode,
        message: 'Missing job_id in OCR response',
        rawBody: res.body,
      );
    }

    return jobId;
  }

  static Future<Map<String, dynamic>> getOcrResult(
      String token, String jobId) async {
    final res = await _sendJson(
      method: 'GET',
      path: '/ocr/result/$jobId',
      token: token,
      timeout: const Duration(seconds: 15),
    );

    return _decodeMapOrThrow(res.body, statusCode: res.statusCode);
  }

  static Future<VehicleOcrResult> pollVehicleOcr(
    String token,
    String jobId, {
    Duration delay = const Duration(seconds: 1),
    Duration timeout = const Duration(seconds: 130),
  }) async {
    final start = DateTime.now();

    while (true) {
      if (DateTime.now().difference(start) > timeout) {
        throw AppApiException(
          statusCode: 408,
          message: 'OCR timeout: result not ready after ${timeout.inSeconds}s',
        );
      }

      final data = await getOcrResult(token, jobId);
      final status = (data['status'] ?? '').toString().trim().toLowerCase();

      if (status == 'success') {
        final result = data['result'];
        if (result is Map) {
          return VehicleOcrResult.fromJson(Map<String, dynamic>.from(result));
        }
        return VehicleOcrResult.empty();
      }

      if (status == 'failed' || status == 'error') {
        throw AppApiException(
          statusCode: 500,
          message: data['error']?.toString() ?? 'OCR failed',
        );
      }

      await Future.delayed(delay);
    }
  }

  static Future<VehicleOcrResult> readVehicleFromImage(
    String token,
    File imageFile,
  ) async {
    final jobId = await requestPlateOcr(token, imageFile);
    return pollVehicleOcr(token, jobId);
  }

  static Future<String> readPlateFromImage(String token, File imageFile) async {
    final v = await readVehicleFromImage(token, imageFile);
    return v.plateNumber;
  }

  static Future<Map<String, dynamic>> searchViolations(
    String token, {
    String? plate,
    String? from,
    String? to,
    int? perPage,
    int? page,
  }) async {
    final query = {
      if (plate != null && plate.isNotEmpty) 'plate': plate,
      if (from != null && from.isNotEmpty) 'from': from,
      if (to != null && to.isNotEmpty) 'to': to,
      if (perPage != null) 'per_page': perPage.toString(),
      if (page != null) 'page': page.toString(),
    };

    final res = await _sendJson(
      method: 'GET',
      path: '/search-violations',
      token: token,
      queryParameters: query,
    );

    final decoded = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final data = _extractList(decoded);
    final meta = _extractPaginationMeta(decoded);

    return {
      'data': data,
      'meta': meta,
    };
  }

  static Future<String> requestStt(String token, File audioFile) async {
    final streamed = await _sendMultipart(
      path: '/stt/transcribe',
      token: token,
      filePaths: [
        _MultipartFilePath(field: 'audio', path: audioFile.path),
      ],
      timeout: const Duration(seconds: 60),
    );

    final res = await http.Response.fromStream(streamed);
    final decoded = _decodeMapOrThrow(res.body, statusCode: res.statusCode);
    final jobId = _extractJobId(decoded);

    if (jobId == null || jobId.isEmpty) {
      throw AppApiException(
        statusCode: res.statusCode,
        message: 'Missing job_id in STT response',
        rawBody: res.body,
      );
    }

    return jobId;
  }

  static Future<Map<String, dynamic>> getSttResult(
      String token, String jobId) async {
    final res = await _sendJson(
      method: 'GET',
      path: '/stt/result/$jobId',
      token: token,
      timeout: const Duration(seconds: 15),
    );

    return _decodeMapOrThrow(res.body, statusCode: res.statusCode);
  }

  static Future<Map<String, dynamic>> pollStt(
    String token,
    String jobId, {
    Duration delay = const Duration(seconds: 1),
    Duration timeout = const Duration(seconds: 130),
  }) async {
    final start = DateTime.now();

    while (true) {
      if (DateTime.now().difference(start) > timeout) {
        throw AppApiException(
          statusCode: 408,
          message: 'STT timeout: result not ready after ${timeout.inSeconds}s',
        );
      }

      final data = await getSttResult(token, jobId);
      final status = (data['status'] ?? '').toString().trim().toLowerCase();

      if (status == 'success' || status == 'completed' || status == 'done') {
        return data;
      }

      if (status == 'failed' || status == 'error') {
        throw AppApiException(
          statusCode: 500,
          message: data['error']?.toString() ?? 'STT failed',
        );
      }

      await Future.delayed(delay);
    }
  }

  static Future<http.Response> _sendJson({
    required String method,
    required String path,
    String? token,
    bool requiresAuth = true,
    Map<String, dynamic>? body,
    Map<String, String>? queryParameters,
    Duration timeout = _defaultTimeout,
  }) async {
    final uri = Uri.parse('${Config.baseUrl}$path').replace(
      queryParameters: queryParameters,
    );

    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (requiresAuth) {
      if (token == null || token.isEmpty) {
        throw const AppApiException(statusCode: 401, message: 'Missing token');
      }
      headers['Authorization'] = 'Bearer $token';
    }

    Future<http.Response> run() {
      switch (method.toUpperCase()) {
        case 'GET':
          return http.get(uri, headers: headers).timeout(timeout);
        case 'POST':
          return http
              .post(
                uri,
                headers: headers,
                body: body == null ? null : jsonEncode(body),
              )
              .timeout(timeout);
        default:
          throw UnsupportedError('Unsupported method: $method');
      }
    }

    final response = await _runWithRetry(run);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw _buildApiException(response.statusCode, response.body);
    }

    return response;
  }

  static Future<http.StreamedResponse> _sendMultipart({
    required String path,
    required String token,
    required List<_MultipartFilePath> filePaths,
    Map<String, String>? fields,
    Duration timeout = _defaultTimeout,
  }) async {
    final uri = Uri.parse('${Config.baseUrl}$path');

    Future<http.StreamedResponse> run() async {
      final req = http.MultipartRequest('POST', uri);
      req.headers['Accept'] = 'application/json';
      req.headers['Authorization'] = 'Bearer $token';
      if (fields != null) {
        req.fields.addAll(fields);
      }
      for (final filePath in filePaths) {
        req.files.add(
          await http.MultipartFile.fromPath(filePath.field, filePath.path),
        );
      }
      return req.send().timeout(timeout);
    }

    final response = await _runWithRetry(run);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final body = await response.stream.bytesToString();
      throw _buildApiException(response.statusCode, body);
    }

    return response;
  }

  static Future<T> _runWithRetry<T>(Future<T> Function() action) async {
    int attempt = 0;
    Object? lastError;

    while (attempt < _maxAttempts) {
      attempt++;
      try {
        return await action();
      } on TimeoutException catch (e) {
        lastError = e;
      } on SocketException catch (e) {
        lastError = e;
      } catch (e) {
        rethrow;
      }

      if (attempt < _maxAttempts) {
        await Future.delayed(Duration(milliseconds: 400 * attempt));
      }
    }

    throw AppApiException(
      statusCode: 0,
      message: 'Network request failed after $_maxAttempts attempts',
      rawBody: lastError.toString(),
    );
  }

  static dynamic _decodeMapOrListOrThrow(
    String body, {
    required int statusCode,
  }) {
    final decoded = _decodeJsonSafe(body);
    if (decoded is Map<String, dynamic> || decoded is List<dynamic>) {
      return decoded;
    }

    _logParseFailure(body, statusCode, 'Expected map or list');
    throw AppApiException(
      statusCode: statusCode,
      message: 'Invalid server response format',
      rawBody: body,
    );
  }

  static Map<String, dynamic> _decodeMapOrThrow(
    String body, {
    required int statusCode,
  }) {
    final decoded = _decodeJsonSafe(body);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }

    _logParseFailure(body, statusCode, 'Expected map');
    throw AppApiException(
      statusCode: statusCode,
      message: 'Invalid server response format',
      rawBody: body,
    );
  }

  static dynamic _decodeJsonSafe(String body) {
    final trimmed = body.trim();
    if (trimmed.isEmpty) {
      return null;
    }

    try {
      return jsonDecode(trimmed);
    } catch (_) {
      final cleaned = trimmed.replaceFirst('\uFEFF', '');
      if (cleaned != trimmed) {
        try {
          return jsonDecode(cleaned);
        } catch (_) {}
      }

      final extracted = _extractJsonEnvelope(cleaned);
      if (extracted != null) {
        try {
          return jsonDecode(extracted);
        } catch (_) {}
      }

      return null;
    }
  }

  static String? _extractJsonEnvelope(String body) {
    final objectStart = body.indexOf('{');
    final objectEnd = body.lastIndexOf('}');
    if (objectStart != -1 && objectEnd > objectStart) {
      return body.substring(objectStart, objectEnd + 1);
    }

    final listStart = body.indexOf('[');
    final listEnd = body.lastIndexOf(']');
    if (listStart != -1 && listEnd > listStart) {
      return body.substring(listStart, listEnd + 1);
    }

    return null;
  }

  static AppApiException _buildApiException(int statusCode, String body) {
    final decoded = _decodeJsonSafe(body);

    if (decoded is Map<String, dynamic>) {
      final normalized = _normalizeMessage(decoded);
      final message = _firstNonEmptyString([
            normalized['message'],
            normalized['error'],
          ]) ??
          _defaultMessageFor(statusCode);

      return AppApiException(
        statusCode: (normalized['status_code'] as num?)?.toInt() ?? statusCode,
        message: message,
        errors: _asMapOfStringList(normalized['errors']),
        rawBody: body,
      );
    }

    return AppApiException(
      statusCode: statusCode,
      message: _defaultMessageFor(statusCode),
      rawBody: body,
    );
  }

  static String _defaultMessageFor(int statusCode) {
    if (statusCode == 401) return 'انتهت الجلسة أو لم يتم تسجيل الدخول.';
    if (statusCode == 422) return 'البيانات المدخلة غير صالحة.';
    if (statusCode >= 500) return 'حدث خطأ في الخادم.';
    return 'فشل تنفيذ الطلب ($statusCode).';
  }

  static Map<String, dynamic> _normalizeMessage(Map<String, dynamic> map) {
    final out = Map<String, dynamic>.from(map);
    final message = _firstNonEmptyString([out['message'], out['massage']]);
    if (message != null) {
      out['message'] = message;
    }
    return out;
  }

  static dynamic _extractData(dynamic decoded) {
    dynamic current = decoded;

    while (true) {
      final map = _asMap(current);
      if (map == null) {
        return current;
      }

      dynamic next;
      for (final key in const ['data', 'item', 'result']) {
        if (map.containsKey(key) && map[key] != null) {
          next = map[key];
          break;
        }
      }

      if (next == null || identical(next, current)) {
        return current;
      }

      current = next;
    }
  }

  static Future<List<dynamic>> _getLookupList({
    required String token,
    required String path,
    required String entity,
  }) async {
    final res = await _sendJson(method: 'GET', path: path, token: token);
    final decoded =
        _decodeMapOrListOrThrow(res.body, statusCode: res.statusCode);

    if (decoded is List<dynamic>) {
      return decoded;
    }

    final data = _extractData(decoded);
    if (data is List<dynamic>) {
      return data;
    }

    _logParseFailure(res.body, res.statusCode, 'Expected list for $entity');
    throw AppApiException(
      statusCode: res.statusCode,
      message: 'Unexpected response format: expected list of $entity',
      rawBody: res.body,
    );
  }

  static String? _extractJobId(Map<String, dynamic> response) {
    final data = _asMap(response['data']);
    return _firstNonEmptyString([
      response['job_id'],
      data?['job_id'],
    ]);
  }

  static List<dynamic> _extractList(dynamic decoded) {
    final queue = <dynamic>[decoded];
    final visited = <Object?>{};

    while (queue.isNotEmpty) {
      final current = queue.removeAt(0);

      if (current is List<dynamic>) {
        return current;
      }

      final map = _asMap(current);
      if (map == null) {
        continue;
      }

      if (!visited.add(current)) {
        continue;
      }

      for (final key in const ['data', 'items', 'results', 'rows']) {
        final nested = map[key];
        if (nested is List<dynamic>) {
          return nested;
        }
        if (nested != null) {
          queue.add(nested);
        }
      }
    }

    return <dynamic>[];
  }

  static Map<String, dynamic> _extractPaginationMeta(dynamic decoded) {
    final queue = <dynamic>[decoded];
    final visited = <Object?>{};

    while (queue.isNotEmpty) {
      final current = queue.removeAt(0);
      final map = _asMap(current);

      if (map == null) {
        continue;
      }

      if (!visited.add(current)) {
        continue;
      }

      final explicitMeta = _asMap(map['meta']);
      if (explicitMeta != null) {
        final paginationMeta = _paginationFieldsOnly(explicitMeta);
        if (paginationMeta.isNotEmpty) {
          return paginationMeta;
        }
      }

      final directPagination = _paginationFieldsOnly(map);
      if (directPagination.isNotEmpty) {
        return directPagination;
      }

      for (final key in const ['data', 'item', 'result']) {
        final nested = map[key];
        if (nested != null) {
          queue.add(nested);
        }
      }
    }

    return const <String, dynamic>{};
  }

  static Map<String, dynamic> _paginationFieldsOnly(Map<String, dynamic> map) {
    final out = <String, dynamic>{};

    for (final key in const [
      'current_page',
      'last_page',
      'per_page',
      'total',
      'from',
      'to',
    ]) {
      if (map.containsKey(key)) {
        out[key] = map[key];
      }
    }

    return out;
  }

  static Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return Map<String, dynamic>.from(value);
    }
    return null;
  }

  static Map<String, dynamic>? _toMapItem(dynamic item) {
    if (item is Map<String, dynamic>) {
      return item;
    }
    if (item is Map) {
      return Map<String, dynamic>.from(item);
    }
    if (item is String && item.trim().startsWith('{')) {
      try {
        final decoded = jsonDecode(item);
        if (decoded is Map<String, dynamic>) return decoded;
        if (decoded is Map) return Map<String, dynamic>.from(decoded);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  static String? _firstNonEmptyString(List<dynamic> values) {
    for (final value in values) {
      final text = value?.toString().trim();
      if (text != null && text.isNotEmpty) {
        return text;
      }
    }
    return null;
  }

  static Map<String, List<String>>? _asMapOfStringList(dynamic value) {
    if (value is! Map) {
      return null;
    }

    final result = <String, List<String>>{};
    for (final entry in value.entries) {
      final key = entry.key.toString();
      final item = entry.value;
      if (item is List) {
        result[key] = item.map((e) => e.toString()).toList();
      } else if (item != null) {
        result[key] = [item.toString()];
      }
    }

    return result.isEmpty ? null : result;
  }

  static void _logParseFailure(String body, int statusCode, String reason) {
    final preview = body.length > 2000 ? '${body.substring(0, 2000)}...' : body;
    debugPrint(
      'API parse failure: $reason, status=$statusCode, body=$preview',
    );
  }
}

class AppApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, List<String>>? errors;
  final String? rawBody;

  const AppApiException({
    required this.statusCode,
    required this.message,
    this.errors,
    this.rawBody,
  });

  @override
  String toString() {
    if (errors == null || errors!.isEmpty) {
      return message;
    }

    final details = errors!.entries
        .map((e) => '${e.key}: ${e.value.join(', ')}')
        .join(' | ');
    return '$message ($details)';
  }
}

class _MultipartFilePath {
  final String field;
  final String path;

  const _MultipartFilePath({
    required this.field,
    required this.path,
  });
}
