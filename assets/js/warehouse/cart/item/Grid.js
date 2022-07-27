Ext.define('GibsonOS.module.hc.warehouse.cart.item.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcWarehouseCartItemGrid'],
    multiSelect: true,
    addFunction() {
        const me = this;
        const record = me.getStore().add({stock: 1})[0];

        me.plugins[0].startEdit(record, 1);
    },
    deleteFunction(records) {
        // this.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.cart.Item({
            cartId: me.cartId
        });

        me.plugins = [
            Ext.create('Ext.grid.plugin.RowEditing', {
                saveBtnText: 'Speichern',
                cancelBtnText: 'Abbrechen',
                clicksToMoveEditor: 1,
                pluginId: 'rowEditing',
                listeners: {
                    beforeedit: function(editor, context) {
                    }
                }
            })
        ];

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'itemId',
            flex: 1,
            editor: {
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.box.Item',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\Box\\ItemAutoComplete',
                    }
                },
                hideLabel: true
            }
        },{
            header: 'Anzahl',
            dataIndex: 'stock',
            flex: 1,
            editor: {
                xtype: 'gosFormNumberfield',
                hideLabel: true,
                minValue: 1
            }
        }];
    }
});