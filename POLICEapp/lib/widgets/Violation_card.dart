import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../core/police_theme.dart';
import '../models/violation.dart';
import 'app_card.dart';

class ViolationCard extends StatelessWidget {
  const ViolationCard({
    super.key,
    required this.violation,
    this.onTap,
  });

  final Violation violation;
  final VoidCallback? onTap;

  String _formatDate(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Date unavailable';
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
    final plate =
        violation.vehicleSnapshot?['plate_number']?.toString() ?? 'Unknown plate';
    final typeName = violation.violationType?['name']?.toString() ??
        'Unknown violation type';
    final place = [
      violation.locationCityName,
      violation.locationStreetName,
      violation.locationLandmark,
    ].whereType<String>().where((e) => e.isNotEmpty).join(' • ');

    return AppCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: PoliceTheme.primary.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(
                  Icons.local_police_outlined,
                  color: PoliceTheme.primary,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      plate,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      typeName,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right_rounded,
                color: PoliceTheme.textSecondary,
              ),
            ],
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _InfoPill(
                icon: Icons.place_outlined,
                label: place.isEmpty ? 'Location unavailable' : place,
              ),
              _InfoPill(
                icon: Icons.schedule_outlined,
                label: _formatDate(
                  violation.occurredAt.isNotEmpty
                      ? violation.occurredAt
                      : violation.createdAt,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: PoliceTheme.textSecondary),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: PoliceTheme.textPrimary,
                  ),
            ),
          ),
        ],
      ),
    );
  }
}
