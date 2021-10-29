Ext.define('GibsonOS.module.hc.index.module.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcIndexModuleGrid'],
    itemId: 'hcIndexModuleGrid',
    viewConfig: {
        getRowClass: function (record) {
            if (record.get('offline')) {
                return 'hcModuleOffline';
            }
        },
        listeners: {
            render: function (view) {
                let grid = view.up('gridpanel');

                grid.dragZone = Ext.create('Ext.dd.DragZone', view.getEl(), {
                    getDragData: function (event) {
                        let sourceElement = event.getTarget().parentNode.parentNode;
                        let record = view.getRecord(sourceElement);

                        if (sourceElement) {
                            let clone = sourceElement.cloneNode(true);
                            let data = {
                                module: 'hc',
                                task: 'module',
                                action: 'view',
                                text: record.get('name'),
                                icon: record.get('settings') && record.get('settings').icon ? record.get('settings').icon : 'icon_homecontrol',
                                params: record.getData()
                            };

                            return grid.dragData = {
                                sourceEl: sourceElement,
                                repairXY: Ext.fly(sourceElement).getXY(),
                                ddel: clone,
                                shortcut: data
                            };
                        }
                    },
                    getRepairXY: function () {
                        return this.dragData.repairXY;
                    }
                });
            }
        }
    },
    initComponent: function (arguments) {
        let me = this;

        me.store = new GibsonOS.module.hc.index.store.Module({gos: me.gos});
        // me.dockedItems = [{
        //     xtype: 'gosToolbarPaging',
        //     itemId: 'hcIndexModulePaging',
        //     store: this.store,
        //     displayMsg: 'Module {0} - {1} von {2}',
        //     emptyMsg: 'Keine Module vorhanden'
        // }];

        me.callParent(arguments);
    },
    enterFunction(module) {
        hcModuleView(module.getData());
    },
    addFunction() {
        const me = this;
        const addWindow = new GibsonOS.module.hc.module.add.Window({
            masterId: 0
        }).show();
        const form = addWindow.down('form').getForm();

        form.on('actioncomplete', () => {
            me.getStore().load();
        });
        form.findField('masterId').setValue(me.gos.data.extraParams.masterId);
    },
    deleteFunction(records) {
        const me = this;
        const grid = me.down('gosModuleHcIndexModuleGrid');
        let message = 'Möchten Sie die ' + records.length + ' Module wirklich löchen?';

        if (records.length === 1) {
            message = 'Möchten Sie das Modul ' + records[0].get('name') + ' wirklich löchen?';
        }

        GibsonOS.MessageBox.show({
            title: 'Modul löschen?',
            msg: message,
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler: function() {
                    grid.setLoading(true);

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/slave/delete',
                        params: {
                            id: record.get('id')
                        },
                        success: function() {
                            grid.getStore().remove(record);
                            grid.setLoading(false);
                        }
                    });
                }
            },{
                text: 'Nein'
            }]
        });
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Typ',
            dataIndex: 'type',
            width: 100
        },{
            header: 'Adresse',
            dataIndex: 'address',
            width: 50,
            align: 'right'
        },{
            header: 'Hinzugefügt',
            dataIndex: 'added',
            width: 130,
            align: 'right'
        },{
            header: 'Letztes Update',
            dataIndex: 'modified',
            width: 130,
            align: 'right'
        }];
    }
});