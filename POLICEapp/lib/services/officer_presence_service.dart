import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import 'api_service.dart';
import 'secure_storage.dart';

class OfficerPresenceService {
  OfficerPresenceService._();

  static Timer? _timer;
  static StreamSubscription<Position>? _positionSubscription;
  static Position? _lastPosition;
  static bool _isSyncing = false;

  static const Duration _syncInterval = Duration(seconds: 45);
  static const LocationSettings _locationSettings = LocationSettings(
    accuracy: LocationAccuracy.high,
    distanceFilter: 10,
  );

  static Future<void> start() async {
    if (_timer != null || _positionSubscription != null) {
      await syncNow();
      return;
    }

    await syncNow();
    _timer = Timer.periodic(_syncInterval, (_) {
      unawaited(syncNow());
    });

    _positionSubscription =
        Geolocator.getPositionStream(locationSettings: _locationSettings)
            .listen((position) {
      _lastPosition = position;
      _log(
        'position stream update: ${position.latitude}, ${position.longitude}',
      );
      unawaited(_syncPosition(position));
    }, onError: (e) {
      _log('position stream error: $e');
    });
  }

  static Future<void> stop() async {
    _timer?.cancel();
    _timer = null;
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    await _sendOfflineStatusIfPossible();
  }

  static Future<void> syncNow() async {
    try {
      final token = await SecureStorage.readToken();
      if (!_hasUsableToken(token)) {
        _log('no token, skipping sync');
        return;
      }

      final position = await _resolveCurrentPosition();
      if (position == null) {
        return;
      }

      await _syncPosition(position, token: token);
    } catch (e, st) {
      _log('failed to sync live location: $e\n$st');
      // Presence sync is best-effort and should not interrupt the UI flow.
    }
  }

  static Future<void> _syncPosition(
    Position position, {
    String? token,
    String availabilityStatus = 'available',
  }) async {
    if (_isSyncing) {
      return;
    }

    _isSyncing = true;

    try {
      final authToken = token ?? await SecureStorage.readToken();
      if (!_hasUsableToken(authToken)) {
        _log('no token, skipping position sync');
        return;
      }

      _lastPosition = position;
      _log('sending location: ${position.latitude}, ${position.longitude}');

      final response = await ApiService.updateOfficerLiveLocation(
        authToken!,
        latitude: position.latitude,
        longitude: position.longitude,
        availabilityStatus: availabilityStatus,
      );

      _log(
        'live location sent successfully: ${response.statusCode} ${response.body}',
      );
    } catch (e, st) {
      _log('failed to sync live location: $e\n$st');
    } finally {
      _isSyncing = false;
    }
  }

  static Future<Position?> _resolveCurrentPosition() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      _log('location service disabled');
      return null;
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      _log('location permission denied');
      return null;
    }

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
      ),
    );
  }

  static Future<void> _sendOfflineStatusIfPossible() async {
    final token = await SecureStorage.readToken();
    if (!_hasUsableToken(token)) {
      return;
    }

    Position? position = _lastPosition;
    if (position == null) {
      try {
        position = await _resolveCurrentPosition();
      } catch (e, st) {
        _log('failed to get last position on stop: $e\n$st');
      }
    }

    if (position == null) {
      _log('no position available to send offline status');
      return;
    }

    try {
      await ApiService.updateOfficerLiveLocation(
        token!,
        latitude: position.latitude,
        longitude: position.longitude,
        availabilityStatus: 'offline',
      );
      _log('sent offline status before stop');
    } catch (e, st) {
      _log('failed to send offline status: $e\n$st');
    }
  }

  static bool _hasUsableToken(String? token) {
    return token != null && token.isNotEmpty;
  }

  static void _log(String message) {
    debugPrint('[OfficerPresence] $message');
  }
}
