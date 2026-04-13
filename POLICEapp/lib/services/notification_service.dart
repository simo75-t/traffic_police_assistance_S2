import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_service.dart';
import 'secure_storage.dart';

final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class NotificationService {
  NotificationService._();

  static final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static bool _initialized = false;
  static bool _tokenRefreshRegistered = false;

  static const AndroidNotificationChannel _channel = AndroidNotificationChannel(
    'dispatch_alerts',
    'Dispatch Alerts',
    description: 'Notifications for citizen report dispatch assignments.',
    importance: Importance.high,
  );

  static Future<void> initialize() async {
    if (_initialized) return;

    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    );

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload == null || payload.isEmpty) return;
        _handlePayload(jsonDecode(payload) as Map<String, dynamic>);
      },
    );

    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_channel);

    await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleRemoteMessage);

    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleRemoteMessage(initialMessage);
    }

    _initialized = true;
  }

  static Future<void> syncTokenWithBackend() async {
    await initialize();

    final authToken = await SecureStorage.readToken();
    if (authToken == null || authToken.isEmpty) return;

    final fcmToken = await _messaging.getToken();
    if (fcmToken == null || fcmToken.isEmpty) return;

    final savedToken = await SecureStorage.readFcmToken();
    if (savedToken != fcmToken) {
      await ApiService.updateFcmToken(authToken, fcmToken);
      await SecureStorage.saveFcmToken(fcmToken);
    }

    if (_tokenRefreshRegistered) return;
    _tokenRefreshRegistered = true;

    _messaging.onTokenRefresh.listen((newToken) async {
      final latestAuthToken = await SecureStorage.readToken();
      if (latestAuthToken == null || latestAuthToken.isEmpty) return;

      await ApiService.updateFcmToken(latestAuthToken, newToken);
      await SecureStorage.saveFcmToken(newToken);
    });
  }

  static Future<void> clearStoredFcmToken() async {
    await SecureStorage.deleteFcmToken();
  }

  static Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          _channel.id,
          _channel.name,
          channelDescription: _channel.description,
          importance: Importance.high,
          priority: Priority.high,
        ),
      ),
      payload: jsonEncode(message.data),
    );
  }

  static void _handleRemoteMessage(RemoteMessage message) {
    _handlePayload(message.data);
  }

  static void _handlePayload(Map<String, dynamic> payload) {
    final reportId = int.tryParse(payload['report_id']?.toString() ?? '');
    appNavigatorKey.currentState?.pushNamed(
      '/dispatch-assignments',
      arguments: reportId,
    );
  }
}
