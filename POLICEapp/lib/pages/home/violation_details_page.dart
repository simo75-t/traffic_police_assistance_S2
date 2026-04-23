import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../models/violation.dart';
import '../../services/violation_pdf_service.dart';
import '../../utils/data_utils.dart';
import 'violation_pdf_preview_page.dart';

class ViolationDetailsPage extends StatefulWidget {
  final Violation violation;

  const ViolationDetailsPage({super.key, required this.violation});

  @override
  State<ViolationDetailsPage> createState() => _ViolationDetailsPageState();
}

class _ViolationDetailsPageState extends State<ViolationDetailsPage> {
  bool _pdfLoading = false;

  String _formatDate(String? occurredAt, String? createdAt) {
    final dt = AppDateUtils.violationDate(
      occurredAt: occurredAt,
      createdAt: createdAt,
    );

    if (dt == null) return '—';

    final local = dt.toLocal();
    return DateFormat('yyyy-MM-dd – HH:mm').format(local);
  }

  Widget infoRow(IconData icon, String label, dynamic value) {
    final text = (value == null || value.toString().trim().isEmpty)
        ? "—"
        : value.toString();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, color: Colors.blueAccent, size: 22),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label,
                    style: const TextStyle(fontSize: 12, color: Colors.grey)),
                Text(
                  text,
                  style: const TextStyle(fontSize: 16, color: Colors.white),
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to prepare PDF: $e')),
      );
    } finally {
      if (mounted) {
        setState(() => _pdfLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final violation = widget.violation;
    final plate = violation.plateNumber ?? "No Plate";

    return Scaffold(
      backgroundColor: const Color(0xFF050814),
      appBar: AppBar(
        title: const Text("Violation Details"),
        backgroundColor: const Color(0xFF050814),
        elevation: 0,
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
            tooltip: 'Open PDF',
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(22),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: const Color(0xFF101424),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Colors.white10),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.local_police, color: Colors.amber, size: 30),
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
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _pdfLoading ? null : _openPdf,
                  icon: const Icon(Icons.picture_as_pdf),
                  label: Text(
                      _pdfLoading ? 'Preparing PDF...' : 'Open Violation PDF'),
                ),
              ),
              const SizedBox(height: 20),
              const Divider(color: Colors.white12),
              Text(
                "Vehicle Information",
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.blue.shade200,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),
              infoRow(Icons.directions_car, "Plate", violation.plateNumber),
              infoRow(Icons.person, "Owner", violation.ownerName),
              const SizedBox(height: 18),
              const Divider(color: Colors.white12),
              Text(
                "Location",
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.blue.shade200,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),
              infoRow(Icons.location_city, "City", violation.locationCityName),
              infoRow(Icons.map, "Street", violation.locationStreetName),
              infoRow(Icons.place, "Landmark", violation.locationLandmark),
              infoRow(
                  Icons.home_outlined, "Address", violation.locationAddress),
              infoRow(Icons.pin_drop, "Latitude", violation.locationLatitude),
              infoRow(Icons.pin_drop, "Longitude", violation.locationLongitude),
              const SizedBox(height: 18),
              const Divider(color: Colors.white12),
              Text(
                "Violation Details",
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.blue.shade200,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),
              infoRow(Icons.warning, "Type", violation.violationType?['name']),
              infoRow(
                  Icons.money, "Fine Amount", violation.fineAmount?.toString()),
              infoRow(Icons.description, "Description", violation.description),
              infoRow(
                Icons.calendar_today,
                "Date",
                _formatDate(violation.occurredAt, violation.createdAt),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
