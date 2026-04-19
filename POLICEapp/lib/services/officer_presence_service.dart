import 'dart:async';
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
      print(
          '[OfficerPresence] position stream update: ${position.latitude}, ${position.longitude}');
      unawaited(_syncPosition(position));
    }, onError: (e) {
      print('[OfficerPresence] position stream error: $e');
    });
  }

  static Future<void> stop() async {
    final token = await SecureStorage.readToken();
    if (token != null && token.isNotEmpty) {
      var position = _lastPosition;
      if (position == null) {
        try {
          final serviceEnabled = await Geolocator.isLocationServiceEnabled();
          if (serviceEnabled) {
            var permission = await Geolocator.checkPermission();
            if (permission == LocationPermission.denied) {
              permission = await Geolocator.requestPermission();
            }

            if (permission != LocationPermission.denied &&
                permission != LocationPermission.deniedForever) {
              position = await Geolocator.getCurrentPosition(
                locationSettings: const LocationSettings(
                  accuracy: LocationAccuracy.high,
                ),
              );
            }
          }
        } catch (e, st) {
          print(
              '[OfficerPresence] failed to get last position on stop: $e\n$st');
        }
      }

      if (position != null) {
        try {
          await ApiService.updateOfficerLiveLocation(
            token,
            latitude: position.latitude,
            longitude: position.longitude,
            availabilityStatus: 'offline',
          );
          print('[OfficerPresence] sent offline status before stop');
        } catch (e, st) {
          print('[OfficerPresence] failed to send offline status: $e\n$st');
        }
      } else {
        print('[OfficerPresence] no position available to send offline status');
      }
    }

    _timer?.cancel();
    _timer = null;
    await _positionSubscription?.cancel();
    _positionSubscription = null;
  }

  static Future<void> syncNow() async {
    try {
      final token = await SecureStorage.readToken();
      if (token == null || token.isEmpty) {
        print('[OfficerPresence] no token, skipping sync');
        return;
      }

      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        print('[OfficerPresence] location service disabled');
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        print('[OfficerPresence] location permission denied');
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      await _syncPosition(position, token: token);
    } catch (e, st) {
      print('[OfficerPresence] failed to sync live location: $e\n$st');
      // Presence sync is best-effort and should not interrupt the UI flow.
    }
  }

  static Future<void> _syncPosition(
    Position position, {
    String? token,
    String availabilityStatus = 'available',
  }) async {
    if (_isSyncing) return;
    _isSyncing = true;

    try {
      final authToken = token ?? await SecureStorage.readToken();
      if (authToken == null || authToken.isEmpty) {
        print('[OfficerPresence] no token, skipping position sync');
        return;
      }

      _lastPosition = position;

      print(
          '[OfficerPresence] sending location: ${position.latitude}, ${position.longitude}');

      final response = await ApiService.updateOfficerLiveLocation(
        authToken,
        latitude: position.latitude,
        longitude: position.longitude,
        availabilityStatus: availabilityStatus,
      );

      print(
          '[OfficerPresence] live location sent successfully: ${response.statusCode} ${response.body}');
    } catch (e, st) {
      print('[OfficerPresence] failed to sync live location: $e\n$st');
    } finally {
      _isSyncing = false;
    }
  }
}
