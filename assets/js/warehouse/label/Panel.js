Ext.define('GibsonOS.module.hc.warehouse.label.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelPanel'],
    layout: 'border',
    enableToolbar: false,
    initComponent() {
        const me = this;
        const labelView = new GibsonOS.module.hc.warehouse.label.View({
            region: 'north',
            flex: 0,
            split: true,
            height: 150
        });

        me.items = [{
            xtype: 'gosModuleHcWarehouseLabelGrid',
            region: 'west',
            flex: 0,
            split: true,
            width: 200
        },{
            region: 'center',
            itemId: 'center',
            layout: 'border',
            disabled: true,
            addFunction() {
                labelView.getStore().add({
                    left: 1,
                    top: 1,
                    width: 10,
                    height: 10
                });
            },
            deleteFunction(records) {
                labelView.getStore().remove(records);
            },
            viewItem: labelView,
            items: [labelView, {
                xtype: 'gosModuleHcWarehouseLabelElementForm',
                region: 'center',
                disabled: true
            }]
        }];

        me.callParent();

        me.down('gosModuleHcWarehouseLabelGrid').on('selectionchange', (view, records) => {
            const center = me.down('#center');

            if (records.length === 0) {
                center.disable();

                return;
            }

            center.enable();

            const store = labelView.getStore();
            store.getProxy().setExtraParam('id', records[0].get('id'));
            store.load();
        });
        labelView.on('selectionchange', (view, records) => {
            const form = me.down('gosModuleHcWarehouseLabelElementForm');

            if (records.length === 0) {
                form.disable();
                form.getForm().setValues([]);

                return;
            }

            form.enable();
            form.getForm().setValues(records[0].getData());
        });
        me.down('gosModuleHcWarehouseLabelElementForm').getForm().getFields().each((field) => {
            field.on('change', (field, value) => {
                const elements = labelView.getSelectionModel().getSelection();

                if (elements.length !== 1) {
                    return;
                }

                elements[0].set(field.name, value);
            });
        });
        labelView.on('itemkeydown', function(view, record, item, index, event) {
            const form = me.down('gosModuleHcWarehouseLabelElementForm').getForm();
            const moveRecords = function(left, top) {
                Ext.iterate(view.getSelectionModel().getSelection(), function(record) {
                    record.set('left', record.get('left') + left);
                    record.set('top', record.get('top') + top);
                    form.findField('left').setValue(record.get('left'));
                    form.findField('top').setValue(record.get('top'));
                });

                view.getStore().each((key) => key.commit());
            };

            switch (event.getKey()) {
                case Ext.EventObject.S:
                    moveRecords(0, 0.1);
                    break;
                case Ext.EventObject.W:
                    moveRecords(0, -0.1);
                    break;
                case Ext.EventObject.A:
                    moveRecords(-0.1, 0);
                    break;
                case Ext.EventObject.D:
                    moveRecords(0.1, 0);
                    break;
            }
        });
    }
});