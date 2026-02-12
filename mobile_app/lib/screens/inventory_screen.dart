import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/gallon_provider.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key});

  @override
  _InventoryScreenState createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final gallonProvider = Provider.of<GallonProvider>(context, listen: false);
    await gallonProvider.loadStatusSummary();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Inventory'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: Consumer<GallonProvider>(
        builder: (context, provider, _) {
          if (provider.statusSummary == null) {
            return const Center(child: CircularProgressIndicator());
          }

          final summary = provider.statusSummary!;

          return RefreshIndicator(
            onRefresh: _loadData,
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Total Gallons
                  Card(
                    color: Theme.of(context).primaryColor,
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.water_drop, size: 48, color: Colors.white),
                          const SizedBox(width: 16),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Total Gallons',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.white70,
                                ),
                              ),
                              Text(
                                '${summary['total']}',
                                style: const TextStyle(
                                  fontSize: 36,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Status Breakdown
                  const Text(
                    'Status Breakdown',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),

                  _buildStatusItem(
                    'In Station',
                    summary['in_station'],
                    summary['total'],
                    Icons.home,
                    Colors.green,
                  ),
                  const SizedBox(height: 12),

                  _buildStatusItem(
                    'Out (Borrowed)',
                    summary['out'],
                    summary['total'],
                    Icons.exit_to_app,
                    Colors.orange,
                  ),
                  const SizedBox(height: 12),

                  _buildStatusItem(
                    'Overdue',
                    summary['overdue'],
                    summary['total'],
                    Icons.warning,
                    Colors.red,
                  ),
                  const SizedBox(height: 12),

                  _buildStatusItem(
                    'Missing',
                    summary['missing'],
                    summary['total'],
                    Icons.error,
                    Colors.grey[700]!,
                  ),
                  const SizedBox(height: 24),

                  // Alerts
                  if (summary['overdue'] > 0 || summary['missing'] > 0) ...[
                    const Text(
                      'Alerts',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),

                    if (summary['overdue'] > 0)
                      Card(
                        color: Colors.orange[50],
                        child: ListTile(
                          leading: const Icon(Icons.warning, color: Colors.orange),
                          title: const Text('Overdue Gallons'),
                          subtitle: Text('${summary['overdue']} gallon(s) are overdue for return'),
                          trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                          onTap: () {
                            // Navigate to overdue list
                          },
                        ),
                      ),
                    const SizedBox(height: 8),

                    if (summary['missing'] > 0)
                      Card(
                        color: Colors.red[50],
                        child: ListTile(
                          leading: const Icon(Icons.error, color: Colors.red),
                          title: const Text('Missing Gallons'),
                          subtitle: Text('${summary['missing']} gallon(s) marked as missing'),
                          trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                          onTap: () {
                            // Navigate to missing list
                          },
                        ),
                      ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildStatusItem(
    String title,
    int count,
    int total,
    IconData icon,
    Color color,
  ) {
    final percentage = total > 0 ? (count / total * 100).toStringAsFixed(1) : '0.0';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: color, size: 32),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$count gallons ($percentage%)',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            LinearProgressIndicator(
              value: total > 0 ? count / total : 0,
              backgroundColor: Colors.grey[200],
              valueColor: AlwaysStoppedAnimation<Color>(color),
              minHeight: 8,
            ),
          ],
        ),
      ),
    );
  }
}
