import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/violation.dart';

class ViolationCard extends StatelessWidget {
  final Violation violation;
  final VoidCallback? onTap;

  const ViolationCard({
    super.key,
    required this.violation,
    this.onTap,
  });

  String _formatDate(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'تاريخ غير متوفر';
    }

    try {
      final dt = DateTime.parse(value);
      return DateFormat('yyyy-MM-dd HH:mm').format(dt.toLocal());
    } catch (_) {
      return value;
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final plate =
        violation.vehicleSnapshot?['plate_number']?.toString() ?? 'لوحة غير معروفة';
    final typeName =
        violation.violationType?['name']?.toString() ?? 'نوع المخالفة غير معروف';
    final place = [
      violation.locationCityName,
      violation.locationStreetName,
      violation.locationLandmark,
    ].whereType<String>().where((e) => e.isNotEmpty).join(' - ');

    return Card(
      margin: EdgeInsets.zero,
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        leading: CircleAvatar(
          radius: 22,
          backgroundColor: scheme.primary.withValues(alpha: 0.14),
          child: Icon(Icons.local_police, color: scheme.primary),
        ),
        title: Text(
          plate,
          style: const TextStyle(
            fontWeight: FontWeight.w700,
            letterSpacing: 0.4,
          ),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('المخالفة: $typeName'),
              const SizedBox(height: 4),
              Text('الموقع: ${place.isEmpty ? 'غير محدد' : place}'),
              const SizedBox(height: 4),
              Text(
                _formatDate(
                  violation.occurredAt.isNotEmpty
                      ? violation.occurredAt
                      : violation.createdAt,
                ),
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
            ],
          ),
        ),
        trailing: const Icon(Icons.chevron_left),
      ),
    );
  }
}
