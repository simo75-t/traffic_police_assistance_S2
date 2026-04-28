import 'package:flutter/material.dart';

import '../core/police_theme.dart';

class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  factory StatusBadge.fromStatus(String status) {
    final normalized = status.toLowerCase();

    final color = switch (normalized) {
      'queued' => PoliceTheme.warning,
      'processing' || 'submitted' || 'dispatched' =>
        PoliceTheme.processing,
      'done' || 'success' || 'closed' => PoliceTheme.success,
      'warning' || 'under_review' => PoliceTheme.warning,
      'failed' || 'error' => PoliceTheme.error,
      _ => const Color(0xFF64748B),
    };

    final label = switch (normalized) {
      'queued' => 'Queued',
      'processing' => 'Processing',
      'done' => 'Done',
      'submitted' => 'Submitted',
      'dispatched' => 'Dispatched',
      'in_progress' => 'In Progress',
      'under_review' => 'Under Review',
      'closed' => 'Closed',
      _ => status.isEmpty ? 'Unknown' : status,
    };

    return StatusBadge(label: label, color: color);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.28)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
