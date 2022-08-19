Ext.define('GibsonOS.module.hc.warehouse.label.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelPanel'],
    layout: 'border',
    addFunction() {
        const me = this;
        const window = new GibsonOS.module.hc.warehouse.label.Window();

        window.down('form').getForm().on('actioncomplete', () => {
            me.viewItem.getStore().load();
        });
    },
    deleteFunction(records) {
        const me = this;
        let labels = [];

        Ext.iterate(records, (label) => {
            labels.push({id: label.get('id')});
        });

        let title = 'Label löschen';
        let msg = 'Möchten Sie das Label "' + records[0].get('name') + '" wirklich löschen?';

        if (labels.length > 1) {
            title = 'Labels löschen';
            msg = 'Möchten Sie ' + labels.length + ' Labels wirklich löschen?';
        }

        GibsonOS.MessageBox.show({
            title: title,
            msg: msg,
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler: function() {
                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/warehouseLabel/delete',
                        params:  {
                            labels: Ext.encode(labels)
                        },
                        success() {
                            me.viewItem.getStore().load();
                        }
                    });
                }
            },{
                text: 'Nein'
            }]
        });
    },
    initComponent() {
        const me = this;
        const labelGrid = new GibsonOS.module.hc.warehouse.label.Grid({
            region: 'west',
            flex: 0,
            split: true,
            width: 150
        });
        const labelView = new GibsonOS.module.hc.warehouse.label.View({
            region: 'north',
            flex: 0,
            split: true,
            height: 150
        });

        me.viewItem = labelGrid;

        me.items = [labelGrid, {
            region: 'center',
            itemId: 'center',
            layout: 'border',
            disabled: true,
            addFunction() {
                labelView.getStore().add({
                    left: 1,
                    top: 1,
                    width: 10,
                    height: 10,
                    options: {}
                });
            },
            deleteFunction(records) {
                GibsonOS.MessageBox.show({
                    title: 'Element löschen',
                    msg: 'Möchten Sie das Element wirklich löschen?',
                    type: GibsonOS.MessageBox.type.QUESTION,
                    buttons: [{
                        text: 'Ja',
                        handler: function() {
                            labelView.getStore().remove(records);
                        }
                    },{
                        text: 'Nein'
                    }]
                });
            },
            viewItem: labelView,
            items: [labelView, {
                xtype: 'gosModuleHcWarehouseLabelElementForm',
                region: 'center',
                disabled: true
            }]
        }];

        me.callParent();

        me.addAction({
            iconCls: 'icon_system system_save',
            selectionNeeded: 1,
            minSelectionNeeded: 1,
            maxSelectionAllowed: 1,
            handler() {
                const records = labelGrid.getSelectionModel().getSelection();

                if (records.length !== 1) {
                    return;
                }

                const label = records[0];
                let elements = [];

                labelView.getStore().each((element) => {
                    elements.push(element.getData())
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/warehouseLabel/save',
                    params:  {
                        id: label.get('id'),
                        name: label.get('name'),
                        elements: Ext.encode(elements)
                    }
                });
            }
        });

        labelGrid.on('selectionchange', (view, records) => {
            const center = me.down('#center');

            if (records.length !== 1) {
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

            if (records.length !== 1) {
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