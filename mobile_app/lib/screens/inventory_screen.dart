import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/inventory_provider.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  final _searchController = TextEditingController();
  String _selectedCategory = 'all';
  bool _lowStockOnly = false;

  static const _categories = ['all', 'caps', 'seals', 'purification', 'supplies'];

  int _asInt(dynamic value) {
    if (value is int) return value;
    if (value is double) return value.toInt();
    return int.tryParse(value?.toString() ?? '0') ?? 0;
  }

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    final inventoryProvider = Provider.of<InventoryProvider>(context, listen: false);
    await inventoryProvider.refreshData(
      category: _selectedCategory,
      lowStockOnly: _lowStockOnly,
      search: _searchController.text,
    );
  }

  Future<void> _showItemDialog({Map<String, dynamic>? item}) async {
    final formKey = GlobalKey<FormState>();
    final nameController = TextEditingController(text: item?['item_name']?.toString() ?? '');
    final quantityController = TextEditingController(text: item?['quantity']?.toString() ?? '0');
    final unitController = TextEditingController(text: item?['unit']?.toString() ?? 'pcs');
    final unitPriceController = TextEditingController(text: item?['unit_price']?.toString() ?? '0');
    final reorderController = TextEditingController(text: item?['reorder_level']?.toString() ?? '0');
    final descriptionController = TextEditingController(text: item?['description']?.toString() ?? '');
    String category = item?['category']?.toString() ?? 'caps';

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(item == null ? 'Add Inventory Item' : 'Edit Inventory Item'),
          content: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextFormField(
                    controller: nameController,
                    decoration: const InputDecoration(labelText: 'Item Name'),
                    validator: (value) => value == null || value.trim().isEmpty ? 'Item name is required' : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: category,
                    decoration: const InputDecoration(labelText: 'Category'),
                    items: _categories
                        .where((value) => value != 'all')
                        .map((value) => DropdownMenuItem(
                              value: value,
                              child: Text(value[0].toUpperCase() + value.substring(1)),
                            ))
                        .toList(),
                    onChanged: (value) {
                      if (value != null) {
                        category = value;
                      }
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: quantityController,
                    decoration: const InputDecoration(labelText: 'Quantity'),
                    keyboardType: TextInputType.number,
                    validator: (value) => int.tryParse(value ?? '') == null ? 'Enter a valid number' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: unitController,
                    decoration: const InputDecoration(labelText: 'Unit (e.g., pcs, box)'),
                    validator: (value) => value == null || value.trim().isEmpty ? 'Unit is required' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: unitPriceController,
                    decoration: const InputDecoration(labelText: 'Unit Price'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    validator: (value) => double.tryParse(value ?? '') == null ? 'Enter a valid amount' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: reorderController,
                    decoration: const InputDecoration(labelText: 'Reorder Level'),
                    keyboardType: TextInputType.number,
                    validator: (value) => int.tryParse(value ?? '') == null ? 'Enter a valid number' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: descriptionController,
                    decoration: const InputDecoration(labelText: 'Description (optional)'),
                    maxLines: 2,
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                if (formKey.currentState?.validate() != true) {
                  return;
                }
                Navigator.pop(context, true);
              },
              child: const Text('Save'),
            ),
          ],
        );
      },
    );

    if (saved != true || !mounted) {
      return;
    }

    final payload = {
      'item_name': nameController.text.trim(),
      'category': category,
      'quantity': int.parse(quantityController.text.trim()),
      'unit': unitController.text.trim(),
      'unit_price': double.parse(unitPriceController.text.trim()),
      'reorder_level': int.parse(reorderController.text.trim()),
      'description': descriptionController.text.trim(),
    };

    final provider = Provider.of<InventoryProvider>(context, listen: false);
    final itemId = _asInt(item?['id']);
    final success = item == null
        ? await provider.createItem(payload)
      : await provider.updateItem(itemId, payload);

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success
            ? (item == null ? 'Inventory item added' : 'Inventory item updated')
            : (provider.error ?? 'Unable to save inventory item')),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );

    if (success) {
      await _loadData();
    }
  }

  Future<void> _showAdjustDialog(Map<String, dynamic> item) async {
    final adjustmentController = TextEditingController();
    final reasonController = TextEditingController();

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text('Adjust ${item['item_name']}'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Current quantity: ${item['quantity']} ${item['unit']}',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: adjustmentController,
                decoration: const InputDecoration(
                  labelText: 'Adjustment (+/-)',
                  hintText: 'Example: 10 or -5',
                ),
                keyboardType: const TextInputType.numberWithOptions(signed: true),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: reasonController,
                decoration: const InputDecoration(labelText: 'Reason (optional)'),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Apply'),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) {
      return;
    }

    final adjustment = int.tryParse(adjustmentController.text.trim());
    if (adjustment == null || adjustment == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Enter a valid non-zero adjustment'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final provider = Provider.of<InventoryProvider>(context, listen: false);
    final itemId = _asInt(item['id']);
    final success = await provider.adjustQuantity(
      itemId,
      adjustment,
      reason: reasonController.text.trim(),
    );

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success ? 'Stock adjusted' : (provider.error ?? 'Adjustment failed')),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );

    if (success) {
      await _loadData();
    }
  }

  Future<void> _deleteItem(Map<String, dynamic> item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Delete Item'),
          content: Text('Delete ${item['item_name']} from inventory?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Delete', style: TextStyle(color: Colors.red)),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) {
      return;
    }

    final provider = Provider.of<InventoryProvider>(context, listen: false);
    final success = await provider.deleteItem(_asInt(item['id']));

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success ? 'Inventory item deleted' : (provider.error ?? 'Delete failed')),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );

    if (success) {
      await _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Inventory'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showItemDialog(),
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: Consumer<InventoryProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading && provider.statistics == null) {
            return const Center(child: CircularProgressIndicator());
          }

          final stats = provider.statistics ?? {};
          final items = provider.items;
          final lowStockItems = provider.lowStockItems;

          return RefreshIndicator(
            onRefresh: _loadData,
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          decoration: InputDecoration(
                            hintText: 'Search inventory item',
                            prefixIcon: const Icon(Icons.search),
                            suffixIcon: IconButton(
                              icon: const Icon(Icons.send),
                              onPressed: _loadData,
                            ),
                          ),
                          onSubmitted: (_) => _loadData(),
                        ),
                      ),
                      const SizedBox(width: 8),
                      PopupMenuButton<String>(
                        icon: const Icon(Icons.filter_list),
                        onSelected: (value) {
                          setState(() {
                            if (value == 'low_stock') {
                              _lowStockOnly = !_lowStockOnly;
                            } else {
                              _selectedCategory = value;
                            }
                          });
                          _loadData();
                        },
                        itemBuilder: (context) {
                          return [
                            const PopupMenuItem(value: 'all', child: Text('All Categories')),
                            const PopupMenuItem(value: 'caps', child: Text('Caps')),
                            const PopupMenuItem(value: 'seals', child: Text('Seals')),
                            const PopupMenuItem(value: 'purification', child: Text('Purification')),
                            const PopupMenuItem(value: 'supplies', child: Text('Supplies')),
                            PopupMenuItem(
                              value: 'low_stock',
                              child: Text(_lowStockOnly ? 'Show All Stock' : 'Low Stock Only'),
                            ),
                          ];
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  Card(
                    color: Colors.blue.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: _buildKpi('Items', '${stats['total_items'] ?? 0}', Icons.inventory_2),
                          ),
                          Expanded(
                            child: _buildKpi('Low Stock', '${stats['low_stock_items'] ?? 0}', Icons.warning_amber),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  if (lowStockItems.isNotEmpty)
                    Card(
                      color: Colors.orange.shade50,
                      child: ListTile(
                        leading: const Icon(Icons.notification_important, color: Colors.orange),
                        title: const Text('Low Stock Notification'),
                        subtitle: Text('${lowStockItems.length} item(s) are at or below reorder level.'),
                        trailing: TextButton(
                          onPressed: () {
                            setState(() {
                              _lowStockOnly = true;
                            });
                            _loadData();
                          },
                          child: const Text('View'),
                        ),
                      ),
                    ),

                  const SizedBox(height: 12),
                  if (items.isEmpty)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Text(
                          provider.error ?? 'No inventory items found',
                          style: TextStyle(color: Colors.grey.shade700),
                        ),
                      ),
                    ),

                  ...items.map((item) {
                    final quantity = _asInt(item['quantity']);
                    final reorderLevel = _asInt(item['reorder_level']);
                    final isLowStock = quantity <= reorderLevel;

                    return Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    item['item_name']?.toString() ?? 'Unnamed Item',
                                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                  ),
                                ),
                                if (isLowStock)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: Colors.red.shade100,
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: const Text(
                                      'LOW STOCK',
                                      style: TextStyle(
                                        color: Colors.red,
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text('Category: ${item['category']}'),
                            Text('Quantity: $quantity ${item['unit']}'),
                            Text('Reorder Level: $reorderLevel ${item['unit']}'),
                            Text('Unit Price: PHP ${(double.tryParse(item['unit_price'].toString()) ?? 0).toStringAsFixed(2)}'),
                            if ((item['description'] ?? '').toString().trim().isNotEmpty)
                              Text('Notes: ${item['description']}'),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                OutlinedButton.icon(
                                  onPressed: () => _showAdjustDialog(item),
                                  icon: const Icon(Icons.add_chart),
                                  label: const Text('Adjust'),
                                ),
                                const SizedBox(width: 8),
                                OutlinedButton.icon(
                                  onPressed: () => _showItemDialog(item: item),
                                  icon: const Icon(Icons.edit),
                                  label: const Text('Edit'),
                                ),
                                const SizedBox(width: 8),
                                OutlinedButton.icon(
                                  onPressed: () => _deleteItem(item),
                                  icon: const Icon(Icons.delete, color: Colors.red),
                                  label: const Text('Delete', style: TextStyle(color: Colors.red)),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildKpi(String label, String value, IconData icon) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 4),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Icon(icon, color: Theme.of(context).primaryColor),
            const SizedBox(height: 8),
            Text(
              value,
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            Text(
              label,
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ],
        ),
      ),
    );
  }
}
