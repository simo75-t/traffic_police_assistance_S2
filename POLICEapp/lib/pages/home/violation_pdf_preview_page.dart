import 'dart:io';

import 'package:flutter/material.dart';
import 'package:printing/printing.dart';

class ViolationPdfPreviewPage extends StatelessWidget {
  final String filePath;
  final int violationId;

  const ViolationPdfPreviewPage({
    super.key,
    required this.filePath,
    required this.violationId,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('PDF #$violationId'),
      ),
      body: PdfPreview(
        canChangePageFormat: false,
        canChangeOrientation: false,
        canDebug: false,
        build: (_) => File(filePath).readAsBytes(),
      ),
    );
  }
}
