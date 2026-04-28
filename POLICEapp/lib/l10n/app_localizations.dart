import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

class AppLocalizations {
  AppLocalizations(this.locale, this._strings);

  final Locale locale;
  final Map<String, String> _strings;

  static const supportedLocales = [
    Locale('en'),
    Locale('ar'),
  ];

  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates = [
    AppLocalizationsDelegate(),
    GlobalMaterialLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
  ];

  static AppLocalizations of(BuildContext context) {
    final localizations = Localizations.of<AppLocalizations>(
      context,
      AppLocalizations,
    );
    assert(localizations != null, 'AppLocalizations not found in context.');
    return localizations!;
  }

  bool get isRtl => locale.languageCode == 'ar';

  TextDirection get textDirection =>
      isRtl ? TextDirection.rtl : TextDirection.ltr;

  TextAlign get startTextAlign => isRtl ? TextAlign.right : TextAlign.left;

  String tr(String key, {Map<String, String> params = const {}}) {
    var value = _strings[key] ?? key;
    params.forEach((paramKey, paramValue) {
      value = value.replaceAll('{$paramKey}', paramValue);
    });
    return value;
  }

  String get appTitle => tr('app.title');
  String get retry => tr('common.retry');
  String get cancel => tr('common.cancel');
  String get notAvailable => tr('common.notAvailable');
  String get notSpecified => tr('common.notSpecified');
  String get language => tr('common.language');
  String get languageSystem => tr('common.languageSystem');
  String get languageArabic => tr('common.languageArabic');
  String get languageEnglish => tr('common.languageEnglish');
  String get tryAgain => tr('common.tryAgain');
  String get noPlate => tr('common.noPlate');
  String get openPdf => tr('common.openPdf');
  String get preparingPdf => tr('common.preparingPdf');
  String failedToPreparePdf(String error) =>
      tr('common.failedToPreparePdf', params: {'error': error});

  String get dispatchAuthRequired => tr('dispatch.authRequired');
  String get dispatchPageTitle => tr('dispatch.pageTitle');
  String get dispatchLoading => tr('dispatch.loading');
  String get dispatchErrorTitle => tr('dispatch.errorTitle');
  String dispatchErrorSubtitle(String error) =>
      tr('dispatch.errorSubtitle', params: {'error': error});
  String get dispatchEmptyTitle => tr('dispatch.emptyTitle');
  String get dispatchEmptySubtitle => tr('dispatch.emptySubtitle');
  String get dispatchHeaderTitle => tr('dispatch.headerTitle');
  String dispatchHeaderSummary(int count) =>
      tr('dispatch.headerSummary', params: {'count': '$count'});
  String get dispatchWaitingToStart => tr('dispatch.waitingToStart');
  String get dispatchInProgress => tr('dispatch.inProgress');
  String get dispatchUntitled => tr('dispatch.untitled');
  String dispatchAssignedAt(String date) =>
      tr('dispatch.assignedAt', params: {'date': date});
  String get dispatchNoDetails => tr('dispatch.noDetails');
  String get dispatchLocation => tr('dispatch.location');
  String get dispatchDistance => tr('dispatch.distance');
  String dispatchDistanceKm(String value) =>
      tr('dispatch.distanceKm', params: {'value': value});
  String get dispatchReporterData => tr('dispatch.reporterData');
  String get dispatchNoImage => tr('dispatch.noImage');
  String get dispatchImageLoadError => tr('dispatch.imageLoadError');
  String get dispatchClosedNotice => tr('dispatch.closedNotice');
  String get dispatchStartAction => tr('dispatch.startAction');
  String get dispatchCompleteAction => tr('dispatch.completeAction');
  String get dispatchStartSheetTitle => tr('dispatch.startSheetTitle');
  String get dispatchStartSheetSubtitle => tr('dispatch.startSheetSubtitle');
  String get dispatchStartConfirm => tr('dispatch.startConfirm');
  String get dispatchCompleteSheetTitle => tr('dispatch.completeSheetTitle');
  String get dispatchCompleteSheetSubtitle =>
      tr('dispatch.completeSheetSubtitle');
  String get dispatchCompleteConfirm => tr('dispatch.completeConfirm');
  String get dispatchNotesLabel => tr('dispatch.notesLabel');
  String get dispatchNotesHint => tr('dispatch.notesHint');
  String get dispatchStartedSuccess => tr('dispatch.startedSuccess');
  String get dispatchCompletedSuccess => tr('dispatch.completedSuccess');
  String dispatchPriorityLabel(String value) =>
      tr('dispatch.priorityLabel', params: {'value': value});

  String dispatchPriority(String priority) {
    final normalized = priority.toLowerCase();
    switch (normalized) {
      case 'urgent':
        return tr('dispatch.priority.urgent');
      case 'high':
        return tr('dispatch.priority.high');
      case 'medium':
        return tr('dispatch.priority.medium');
      case 'low':
        return tr('dispatch.priority.low');
      default:
        return priority.isEmpty ? notSpecified : priority;
    }
  }

  String dispatchStatus(String status) {
    final normalized = status.toLowerCase();
    switch (normalized) {
      case 'submitted':
        return tr('dispatch.status.submitted');
      case 'dispatched':
        return tr('dispatch.status.dispatched');
      case 'in_progress':
        return tr('dispatch.status.in_progress');
      case 'under_review':
        return tr('dispatch.status.under_review');
      case 'closed':
        return tr('dispatch.status.closed');
      default:
        return status.isEmpty ? notSpecified : status;
    }
  }

  String get profilePageTitle => tr('profile.pageTitle');
  String get profileLoading => tr('profile.loading');
  String get profileErrorTitle => tr('profile.errorTitle');
  String profileErrorSubtitle(String error) =>
      tr('profile.errorSubtitle', params: {'error': error});
  String get profileUpdatedSuccess => tr('profile.updatedSuccess');
  String profileUpdatedError(String error) =>
      tr('profile.updatedError', params: {'error': error});
  String get profileUnknownUser => tr('profile.unknownUser');
  String get profileAccountActive => tr('profile.accountActive');
  String get profileAccountInactive => tr('profile.accountInactive');
  String get profileNoPhone => tr('profile.noPhone');
  String get profileAccountInfoTitle => tr('profile.accountInfoTitle');
  String get profileAccountInfoSubtitle => tr('profile.accountInfoSubtitle');
  String get profileRole => tr('profile.role');
  String get profileCurrentEmail => tr('profile.currentEmail');
  String get profileLastSeen => tr('profile.lastSeen');
  String get profileEditTitle => tr('profile.editTitle');
  String get profileEditSubtitle => tr('profile.editSubtitle');
  String get profileFullName => tr('profile.fullName');
  String get profileEmail => tr('profile.email');
  String get profilePhone => tr('profile.phone');
  String get profileSaveChanges => tr('profile.saveChanges');
  String get profileLogout => tr('profile.logout');
  String get profileValidationName => tr('profile.validation.name');
  String get profileValidationEmail => tr('profile.validation.email');
  String get profileLanguageTitle => tr('profile.languageTitle');
  String get profileLanguageSubtitle => tr('profile.languageSubtitle');

  String roleLabel(String role) {
    switch (role) {
      case 'Police_officer':
        return tr('roles.policeOfficer');
      case 'Police_manager':
        return tr('roles.policeManager');
      case 'admin':
        return tr('roles.admin');
      default:
        return role;
    }
  }

  String get loginTitle => tr('login.title');
  String get loginSubtitle => tr('login.subtitle');
  String get loginSectionTitle => tr('login.sectionTitle');
  String get loginSectionSubtitle => tr('login.sectionSubtitle');
  String get loginEmail => tr('login.email');
  String get loginPassword => tr('login.password');
  String get loginSubmit => tr('login.submit');
  String get loginErrorNoToken => tr('login.errorNoToken');
  String loginErrorGeneric(String error) =>
      tr('login.errorGeneric', params: {'error': error});
  String get loginValidationEmailRequired =>
      tr('login.validation.emailRequired');
  String get loginValidationEmailInvalid =>
      tr('login.validation.emailInvalid');
  String get loginValidationPasswordRequired =>
      tr('login.validation.passwordRequired');
  String get loginValidationPasswordShort =>
      tr('login.validation.passwordShort');

  String get violationsPageTitle => tr('violations.pageTitle');
  String get violationsSectionTitle => tr('violations.sectionTitle');
  String get violationsSectionSubtitle => tr('violations.sectionSubtitle');
  String get violationsLoading => tr('violations.loading');
  String get violationsErrorTitle => tr('violations.errorTitle');
  String get violationsEmptyTitle => tr('violations.emptyTitle');
  String get violationsEmptySubtitle => tr('violations.emptySubtitle');
  String get violationsLoginRequired => tr('violations.loginRequired');

  String get detailsPageTitle => tr('details.pageTitle');
  String get detailsVehicleInfoTitle => tr('details.vehicleInfoTitle');
  String get detailsVehicleInfoSubtitle => tr('details.vehicleInfoSubtitle');
  String get detailsLocationTitle => tr('details.locationTitle');
  String get detailsLocationSubtitle => tr('details.locationSubtitle');
  String get detailsViolationTitle => tr('details.violationTitle');
  String get detailsViolationSubtitle => tr('details.violationSubtitle');
  String get detailsPlate => tr('details.plate');
  String get detailsOwner => tr('details.owner');
  String get detailsCity => tr('details.city');
  String get detailsStreet => tr('details.street');
  String get detailsLandmark => tr('details.landmark');
  String get detailsAddress => tr('details.address');
  String get detailsLatitude => tr('details.latitude');
  String get detailsLongitude => tr('details.longitude');
  String get detailsType => tr('details.type');
  String get detailsFineAmount => tr('details.fineAmount');
  String get detailsDescription => tr('details.description');
  String get detailsDate => tr('details.date');
  String get detailsEmptyValue => tr('details.emptyValue');
  String get detailsOpenViolationPdf => tr('details.openViolationPdf');
}

class AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) {
    return AppLocalizations.supportedLocales
        .any((supported) => supported.languageCode == locale.languageCode);
  }

  @override
  Future<AppLocalizations> load(Locale locale) async {
    final code = isSupported(locale) ? locale.languageCode : 'en';
    final jsonString =
        await rootBundle.loadString('assets/l10n/$code.json');
    final dynamic decoded = json.decode(jsonString);
    final map = (decoded as Map<String, dynamic>).map(
      (key, value) => MapEntry(key, value.toString()),
    );
    return SynchronousFuture(AppLocalizations(Locale(code), map));
  }

  @override
  bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) {
    return false;
  }
}
