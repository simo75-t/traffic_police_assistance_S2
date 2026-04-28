import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/violation.dart';
import '../../services/violation_pdf_service.dart';
import '../../utils/data_utils.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/section_header.dart';
import 'violation_pdf_preview_page.dart';

class ViolationDetailsPage extends StatefulWidget {
  const ViolationDetailsPage({super.key, required this.violation});

  final Violation violation;

  @override
  State<ViolationDetailsPage> createState() => _ViolationDetailsPageState();
}

class _ViolationDetailsPageState extends State<ViolationDetailsPage> {
  bool _pdfLoading = false;

  String _formatDate(String? occurredAt, String? createdAt) {
    final l10n = AppLocalizations.of(context);
    final dt = AppDateUtils.violationDate(
      occurredAt: occurredAt,
      createdAt: createdAt,
    );

    if (dt == null) return l10n.detailsEmptyValue;

    final local = dt.toLocal();
    return DateFormat('yyyy-MM-dd - HH:mm').format(local);
  }

  Widget infoRow(IconData icon, String label, dynamic value) {
    final l10n = AppLocalizations.of(context);
    final text = (value == null || value.toString().trim().isEmpty)
        ? l10n.detailsEmptyValue
        : value.toString();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: PoliceTheme.secondary.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: PoliceTheme.secondary, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 4),
                Text(
                  text,
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: PoliceTheme.textPrimary,
                      ),
                  textAlign: l10n.startTextAlign,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openPdf() async {
    if (_pdfLoading) return;

    setState(() => _pdfLoading = true);

    try {
      final pdfUrl = widget.violation.pdfUrl;
      String? filePath;

      if (pdfUrl == null || pdfUrl.trim().isEmpty) {
        final file = await ViolationPdfService.ensurePdf(
          widget.violation,
          force: true,
        );
        filePath = file.path;
      }

      if (!mounted) return;

      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ViolationPdfPreviewPage(
            filePath: filePath,
            pdfUrl: pdfUrl,
            violationId: widget.violation.id,
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.failedToPreparePdf('$e'))),
      );
    } finally {
      if (mounted) {
        setState(() => _pdfLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final violation = widget.violation;
    final plate = violation.plateNumber ?? l10n.noPlate;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.detailsPageTitle),
        actions: [
          IconButton(
            onPressed: _pdfLoading ? null : _openPdf,
            icon: _pdfLoading
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.picture_as_pdf_outlined),
            tooltip: l10n.openPdf,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            AppCard(
              padding: EdgeInsets.zero,
              backgroundColor: PoliceTheme.primary,
              borderColor: PoliceTheme.primary,
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  gradient: const LinearGradient(
                    colors: [PoliceTheme.primary, PoliceTheme.secondary],
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(
                          Icons.local_police_outlined,
                          color: Colors.white,
                          size: 30,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            plate,
                            style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    AppButton(
                      label: _pdfLoading
                          ? l10n.preparingPdf
                          : l10n.detailsOpenViolationPdf,
                      onPressed: _pdfLoading ? null : _openPdf,
                      icon: Icons.picture_as_pdf_outlined,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SectionHeader(
                    title: l10n.detailsVehicleInfoTitle,
                    subtitle: l10n.detailsVehicleInfoSubtitle,
                  ),
                  const SizedBox(height: 8),
                  infoRow(
                    Icons.directions_car_outlined,
                    l10n.detailsPlate,
                    violation.plateNumber,
                  ),
                  infoRow(
                    Icons.person_outline,
                    l10n.detailsOwner,
                    violation.ownerName,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SectionHeader(
                    title: l10n.detailsLocationTitle,
                    subtitle: l10n.detailsLocationSubtitle,
                  ),
                  const SizedBox(height: 8),
                  infoRow(
                    Icons.location_city_outlined,
                    l10n.detailsCity,
                    violation.locationCityName,
                  ),
                  infoRow(
                    Icons.map_outlined,
                    l10n.detailsStreet,
                    violation.locationStreetName,
                  ),
                  infoRow(
                    Icons.place_outlined,
                    l10n.detailsLandmark,
                    violation.locationLandmark,
                  ),
                  infoRow(
                    Icons.home_outlined,
                    l10n.detailsAddress,
                    violation.locationAddress,
                  ),
                  infoRow(
                    Icons.pin_drop_outlined,
                    l10n.detailsLatitude,
                    violation.locationLatitude,
                  ),
                  infoRow(
                    Icons.pin_drop_outlined,
                    l10n.detailsLongitude,
                    violation.locationLongitude,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SectionHeader(
                    title: l10n.detailsViolationTitle,
                    subtitle: l10n.detailsViolationSubtitle,
                  ),
                  const SizedBox(height: 8),
                  infoRow(
                    Icons.warning_amber_outlined,
                    l10n.detailsType,
                    violation.violationType?['name'],
                  ),
                  infoRow(
                    Icons.payments_outlined,
                    l10n.detailsFineAmount,
                    violation.fineAmount?.toString(),
                  ),
                  infoRow(
                    Icons.notes_outlined,
                    l10n.detailsDescription,
                    violation.description,
                  ),
                  infoRow(
                    Icons.calendar_today_outlined,
                    l10n.detailsDate,
                    _formatDate(violation.occurredAt, violation.createdAt),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
