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

typedef _JobResultFetcher = Future<Map<String, dynamic>> Function();
typedef _JobFailureMessageBuilder = String Function(
    Map<String, dynamic> payload);

class ApiService {
  static const Duration _defaultTimeout = Duration(seconds: 20);
  static const Duration _writeTimeout = Duration(seconds: 30);
  static const Duration _uploadTimeout = Duration(seconds: 60);
  static const Duration _resultTimeout = Duration(seconds: 15);
  static const Duration _jobPollDelay = Duration(seconds: 1);
  static const Duration _jobPollTimeout = Duration(seconds: 130);
  static const int _maxAttempts = 3;

  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    return _post(
      path: '/login',
      body: {
        'email': email,
        'password': password,
      },
      requiresAuth: false,
    ).then(_decodeResponseMap);
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
    final body = _decodeResponseMap(await _get(path: '/profile', token: token));
    return _parseProfile(body);
  }

  static Future<Profile> updateProfile(
    String token, {
    required String name,
    required String email,
    String? phone,
  }) async {
    final response = await _post(
      path: '/profile/update',
      token: token,
      body: {
        'name': name.trim(),
        'email': email.trim(),
        'phone': _trimOrNull(phone),
      },
    );

    return Profile.fromJson(
      _asMap(_extractData(_decodeResponseMap(response))) ??
          const <String, dynamic>{},
    );
  }

  static Future<List<Violation>> getViolations(String token) async {
    final response = await _get(path: '/violations', token: token);
    return _decodeModelList(
      response,
      mapper: Violation.fromJson,
    );
  }

  static Future<Map<String, dynamic>> createViolation(
    String token,
    Map<String, dynamic> data,
  ) async {
    return _decodeResponseMap(
      await _post(
        path: '/create',
        token: token,
        body: data,
        timeout: _writeTimeout,
      ),
    );
  }

  static Future<http.Response> createViolationResponse(
    String token,
    Map<String, dynamic> data,
  ) {
    return _post(
      path: '/create',
      token: token,
      body: data,
      timeout: _writeTimeout,
    );
  }

  static Future<bool> logout(String token) async {
    try {
      await _post(path: '/logout', token: token);
      return true;
    } on AppApiException catch (error) {
      return error.statusCode == 401;
    }
  }

  static Future<void> updateFcmToken(String token, String fcmToken) async {
    await _post(
      path: '/fcm-token',
      token: token,
      body: {'fcm_token': fcmToken},
    );
  }

  static Future<http.Response> updateOfficerLiveLocation(
    String token, {
    required double latitude,
    required double longitude,
    String? availabilityStatus,
  }) {
    return _post(
      path: '/officers/live-location',
      token: token,
      body: {
        'latitude': latitude,
        'longitude': longitude,
        if (_hasText(availabilityStatus))
          'availability_status': availabilityStatus,
      },
    );
  }

  static Future<List<DispatchAssignment>> getDispatchAssignments(
    String token,
  ) async {
    final response = await _get(path: '/officers/assignments', token: token);
    return _decodeModelList(
      response,
      mapper: DispatchAssignment.fromJson,
    );
  }

  static Future<void> startReportProcessing(
    String token, {
    required int assignmentId,
    String? notes,
  }) async {
    await _updateAssignmentStatus(
      token,
      assignmentId: assignmentId,
      action: 'start',
      notes: notes,
    );
  }

  static Future<void> completeReport(
    String token, {
    required int assignmentId,
    String? notes,
  }) async {
    await _updateAssignmentStatus(
      token,
      assignmentId: assignmentId,
      action: 'complete',
      notes: notes,
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
      token: token,
      path: '/ai_cities',
      entity: 'AI cities',
    );
  }

  static Future<List<dynamic>> getAiViolationTypes(String token) async {
    return _getLookupList(
      token: token,
      path: '/ai_violation-types',
      entity: 'AI violation types',
    );
  }

  static Future<String> requestPlateOcr(String token, File imageFile) async {
    return _requestJobId(
      context: 'OCR',
      response: await _sendMultipart(
        path: '/ocr/plate',
        token: token,
        filePaths: [
          _MultipartFilePath(field: 'image', path: imageFile.path),
        ],
        timeout: _uploadTimeout,
      ),
    );
  }

  static Future<Map<String, dynamic>> getOcrResult(
    String token,
    String jobId,
  ) async {
    final response = await _get(
      path: '/ocr/result/$jobId',
      token: token,
      timeout: _resultTimeout,
    );
    return _decodeMapOrThrow(response.body, statusCode: response.statusCode);
  }

  static Future<VehicleOcrResult> pollVehicleOcr(
    String token,
    String jobId, {
    Duration delay = _jobPollDelay,
    Duration timeout = _jobPollTimeout,
  }) async {
    final data = await _pollJobResult(
      fetch: () => getOcrResult(token, jobId),
      delay: delay,
      timeout: timeout,
      timeoutMessage:
          'OCR timeout: result not ready after ${timeout.inSeconds}s',
      failureMessage: (payload) => payload['error']?.toString() ?? 'OCR failed',
      successStatuses: const {'success'},
    );

    final result = data['result'];
    if (result is Map) {
      return VehicleOcrResult.fromJson(Map<String, dynamic>.from(result));
    }

    return VehicleOcrResult.empty();
  }

  static Future<VehicleOcrResult> readVehicleFromImage(
    String token,
    File imageFile,
  ) async {
    final jobId = await requestPlateOcr(token, imageFile);
    return pollVehicleOcr(token, jobId);
  }

  static Future<String> readPlateFromImage(String token, File imageFile) async {
    final result = await readVehicleFromImage(token, imageFile);
    return result.plateNumber;
  }

  static Future<Map<String, dynamic>> searchViolations(
    String token, {
    String? plate,
    String? from,
    String? to,
    int? perPage,
    int? page,
  }) async {
    final decoded = _decodeResponseMap(
      await _get(
        path: '/search-violations',
        token: token,
        queryParameters: {
          if (_hasText(plate)) 'plate': plate!,
          if (_hasText(from)) 'from': from!,
          if (_hasText(to)) 'to': to!,
          if (perPage != null) 'per_page': perPage.toString(),
          if (page != null) 'page': page.toString(),
        },
      ),
    );

    return {
      'data': _extractList(decoded),
      'meta': _extractPaginationMeta(decoded),
    };
  }

  static Future<String> requestStt(String token, File audioFile) async {
    return _requestJobId(
      context: 'STT',
      response: await _sendMultipart(
        path: '/stt/transcribe',
        token: token,
        filePaths: [
          _MultipartFilePath(field: 'audio', path: audioFile.path),
        ],
        timeout: _uploadTimeout,
      ),
    );
  }

  static Future<Map<String, dynamic>> getSttResult(
    String token,
    String jobId,
  ) async {
    final response = await _get(
      path: '/stt/result/$jobId',
      token: token,
      timeout: _resultTimeout,
    );
    return _decodeMapOrThrow(response.body, statusCode: response.statusCode);
  }

  static Future<Map<String, dynamic>> pollStt(
    String token,
    String jobId, {
    Duration delay = _jobPollDelay,
    Duration timeout = _jobPollTimeout,
  }) {
    return _pollJobResult(
      fetch: () => getSttResult(token, jobId),
      delay: delay,
      timeout: timeout,
      timeoutMessage:
          'STT timeout: result not ready after ${timeout.inSeconds}s',
      failureMessage: (payload) => payload['error']?.toString() ?? 'STT failed',
      successStatuses: const {'success', 'completed', 'done'},
    );
  }

  static Future<void> _updateAssignmentStatus(
    String token, {
    required int assignmentId,
    required String action,
    String? notes,
  }) async {
    await _post(
      path: '/officers/assignments/$assignmentId/$action',
      token: token,
      body: {
        if (_hasText(notes)) 'notes': notes,
      },
    );
  }

  static Future<http.Response> _get({
    required String path,
    required String token,
    Map<String, String>? queryParameters,
    Duration timeout = _defaultTimeout,
  }) {
    return _sendJson(
      method: 'GET',
      path: path,
      token: token,
      queryParameters: queryParameters,
      timeout: timeout,
    );
  }

  static Future<http.Response> _post({
    required String path,
    String? token,
    bool requiresAuth = true,
    Map<String, dynamic>? body,
    Duration timeout = _defaultTimeout,
  }) {
    return _sendJson(
      method: 'POST',
      path: path,
      token: token,
      requiresAuth: requiresAuth,
      body: body,
      timeout: timeout,
    );
  }

  static Future<String> _requestJobId({
    required String context,
    required http.StreamedResponse response,
  }) async {
    final resolved = await http.Response.fromStream(response);
    return _extractJobIdOrThrow(resolved, context: context);
  }

  static Profile _parseProfile(Map<String, dynamic> body) {
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

  static List<T> _decodeModelList<T>(
    http.Response response, {
    required T Function(Map<String, dynamic>) mapper,
  }) {
    final decoded = _decodeMapOrListOrThrow(
      response.body,
      statusCode: response.statusCode,
    );

    return _extractList(decoded)
        .map(_toMapItem)
        .whereType<Map<String, dynamic>>()
        .map(mapper)
        .toList();
  }

  static Future<Map<String, dynamic>> _pollJobResult({
    required _JobResultFetcher fetch,
    required Duration delay,
    required Duration timeout,
    required String timeoutMessage,
    required _JobFailureMessageBuilder failureMessage,
    required Set<String> successStatuses,
  }) async {
    final startedAt = DateTime.now();

    while (true) {
      if (DateTime.now().difference(startedAt) > timeout) {
        throw AppApiException(
          statusCode: 408,
          message: timeoutMessage,
        );
      }

      final data = await fetch();
      final status = (data['status'] ?? '').toString().trim().toLowerCase();

      if (successStatuses.contains(status)) {
        return data;
      }

      if (status == 'failed' || status == 'error') {
        throw AppApiException(
          statusCode: 500,
          message: failureMessage(data),
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

    final headers = _buildJsonHeaders(
      token: token,
      requiresAuth: requiresAuth,
    );

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
      final request = http.MultipartRequest('POST', uri)
        ..headers.addAll(_buildMultipartHeaders(token))
        ..fields.addAll(fields ?? const <String, String>{});

      for (final filePath in filePaths) {
        request.files.add(
          await http.MultipartFile.fromPath(filePath.field, filePath.path),
        );
      }

      return request.send().timeout(timeout);
    }

    final response = await _runWithRetry(run);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final body = await response.stream.bytesToString();
      throw _buildApiException(response.statusCode, body);
    }

    return response;
  }

  static Map<String, String> _buildJsonHeaders({
    String? token,
    required bool requiresAuth,
  }) {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (requiresAuth) {
      headers['Authorization'] = _buildAuthorizationHeader(token);
    }

    return headers;
  }

  static Map<String, String> _buildMultipartHeaders(String token) {
    return <String, String>{
      'Accept': 'application/json',
      'Authorization': _buildAuthorizationHeader(token),
    };
  }

  static String _buildAuthorizationHeader(String? token) {
    if (!_hasText(token)) {
      throw const AppApiException(statusCode: 401, message: 'Missing token');
    }

    return 'Bearer ${token!.trim()}';
  }

  static Future<T> _runWithRetry<T>(Future<T> Function() action) async {
    var attempt = 0;
    Object? lastError;

    while (attempt < _maxAttempts) {
      attempt++;
      try {
        return await action();
      } on TimeoutException catch (error) {
        lastError = error;
      } on SocketException catch (error) {
        lastError = error;
      } catch (_) {
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

  static Map<String, dynamic> _decodeResponseMap(http.Response response) {
    return _normalizeMessage(
      _decodeMapOrThrow(response.body, statusCode: response.statusCode),
    );
  }

  static String _extractJobIdOrThrow(
    http.Response response, {
    required String context,
  }) {
    final decoded = _decodeMapOrThrow(
      response.body,
      statusCode: response.statusCode,
    );
    final jobId = _extractJobId(decoded);

    if (!_hasText(jobId)) {
      throw AppApiException(
        statusCode: response.statusCode,
        message: 'Missing job_id in $context response',
        rawBody: response.body,
      );
    }

    return jobId!;
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
    if (statusCode == 401) {
      return 'ط§ظ†طھظ‡طھ ط§ظ„ط¬ظ„ط³ط© ط£ظˆ ظ„ظ… ظٹطھظ… طھط³ط¬ظٹظ„ ط§ظ„ط¯ط®ظˆظ„.';
    }
    if (statusCode == 422) {
      return 'ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط¯ط®ظ„ط© ط؛ظٹط± طµط§ظ„ط­ط©.';
    }
    if (statusCode >= 500) {
      return 'ط­ط¯ط« ط®ط·ط£ ظپظٹ ط§ظ„ط®ط§ط¯ظ….';
    }
    return 'ظپط´ظ„ طھظ†ظپظٹط° ط§ظ„ط·ظ„ط¨ ($statusCode).';
  }

  static Map<String, dynamic> _normalizeMessage(Map<String, dynamic> map) {
    final normalized = Map<String, dynamic>.from(map);
    final message = _firstNonEmptyString([
      normalized['message'],
      normalized['massage'],
    ]);

    if (message != null) {
      normalized['message'] = message;
    }

    return normalized;
  }

  static dynamic _extractData(dynamic decoded) {
    var current = decoded;

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
    final response = await _sendJson(
      method: 'GET',
      path: path,
      token: token,
    );

    final decoded = _decodeMapOrListOrThrow(
      response.body,
      statusCode: response.statusCode,
    );

    if (decoded is List<dynamic>) {
      return decoded;
    }

    final data = _extractData(decoded);
    if (data is List<dynamic>) {
      return data;
    }

    _logParseFailure(
      response.body,
      response.statusCode,
      'Expected list for $entity',
    );

    throw AppApiException(
      statusCode: response.statusCode,
      message: 'Unexpected response format: expected list of $entity',
      rawBody: response.body,
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
      if (map == null || !visited.add(current)) {
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

      if (map == null || !visited.add(current)) {
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

  static Map<String, dynamic> _paginationFieldsOnly(
    Map<String, dynamic> map,
  ) {
    final output = <String, dynamic>{};

    for (final key in const [
      'current_page',
      'last_page',
      'per_page',
      'total',
      'from',
      'to',
    ]) {
      if (map.containsKey(key)) {
        output[key] = map[key];
      }
    }

    return output;
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
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
        if (decoded is Map) {
          return Map<String, dynamic>.from(decoded);
        }
      } catch (_) {
        return null;
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
        result[key] = item.map((element) => element.toString()).toList();
      } else if (item != null) {
        result[key] = [item.toString()];
      }
    }

    return result.isEmpty ? null : result;
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

  static String? _trimOrNull(String? value) {
    final trimmed = value?.trim();
    return trimmed == null || trimmed.isEmpty ? null : trimmed;
  }

  static bool _hasText(String? value) {
    return value != null && value.trim().isNotEmpty;
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
        .map((entry) => '${entry.key}: ${entry.value.join(', ')}')
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
