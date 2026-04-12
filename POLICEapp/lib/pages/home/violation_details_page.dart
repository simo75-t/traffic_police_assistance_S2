import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/violation.dart';
import '../../utils/data_utils.dart';
class ViolationDetailsPage extends StatelessWidget {
  final Violation violation;

  const ViolationDetailsPage({super.key, required this.violation});

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

  @override
  Widget build(BuildContext context) {
    final snap = violation.vehicleSnapshot;

    final plate = snap?['plate_number']?.toString() ?? "No Plate";

    return Scaffold(
      backgroundColor: const Color(0xFF050814),
      appBar: AppBar(
        title: const Text("Violation Details"),
        backgroundColor: const Color(0xFF050814),
        elevation: 0,
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
              // -------- Title ----------
              Row(
                children: [
                  const Icon(Icons.local_police, color: Colors.amber, size: 30),
                  const SizedBox(width: 12),
                  Text(
                    plate,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  )
                ],
              ),

              const SizedBox(height: 20),
              const Divider(color: Colors.white12),

              // --------- Vehicle Info ----------
              Text(
                "Vehicle Information",
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.blue.shade200,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),

              infoRow(Icons.directions_car, "Plate", snap?['plate_number']),
              infoRow(Icons.person, "Owner", snap?['owner_name']),

              const SizedBox(height: 18),
              const Divider(color: Colors.white12),

              // ---------- Location ----------
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
              infoRow(Icons.home_outlined, "Address", violation.locationAddress),
              infoRow(Icons.pin_drop, "Latitude", violation.locationLatitude),
              infoRow(Icons.pin_drop, "Longitude", violation.locationLongitude),

              const SizedBox(height: 18),
              const Divider(color: Colors.white12),

              // ---------- Violation Info ----------
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
              infoRow(Icons.money, "Fine Amount",
                  violation.fineAmount?.toString()),
              infoRow(Icons.description, "Description", violation.description),

              // ✅ FIXED DATE
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
