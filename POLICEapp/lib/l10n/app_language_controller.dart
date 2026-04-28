import 'package:flutter/material.dart';

import '../services/secure_storage.dart';

class AppLanguageController {
  AppLanguageController._();

  static Future<Locale?> loadSavedLocale() async {
    final code = await SecureStorage.readLocaleCode();
    if (code == null || code.trim().isEmpty) {
      return null;
    }
    return Locale(code);
  }

  static Future<void> saveLocale(Locale? locale) async {
    if (locale == null) {
      await SecureStorage.deleteLocaleCode();
      return;
    }
    await SecureStorage.saveLocaleCode(locale.languageCode);
  }
}
