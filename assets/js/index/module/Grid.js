Ext.define('GibsonOS.module.hc.index.module.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcIndexModuleGrid'],
    itemId: 'hcIndexModuleGrid',
    multiSelect: true,
    enableDrag: true,
    getShortcuts(records) {
        let shortcuts = [];

        Ext.iterate(records, (record) => {
            shortcuts.push({
                id: null,
                module: 'hc',
                task: 'module',
                action: 'view',
                text: record.get('name'),
                icon: record.get('settings') && record.get('settings').icon ? record.get('settings').icon : 'icon_homecontrol',
                parameters: record.getData()
            });
        });

        return shortcuts;
    },
    viewConfig: {
        getRowClass(record) {
            if (record.get('offline')) {
                return 'hcModuleOffline';
            }
        }
    },
    initComponent(arguments) {
        let me = this;

        me.store = new GibsonOS.module.hc.index.store.Module({gos: me.gos});

        me.callParent(arguments);
    },
    enterFunction(module) {
        module = module.getData();

        if (
            GibsonOS.module.hc[module.helper] &&
            typeof(GibsonOS.module.hc[module.helper].App) === 'function'
        ) {
            Ext.create('GibsonOS.module.hc.' + module.helper + '.App', {
                gos: {
                    data: {
                        module: module
                    }
                }
            });
            return;
        }

        const id = Ext.id();
        let settings = null;

        if (typeof(eval(module.helper + 'Settings')) === 'function') {
            settings = [{
                type:'gear',
                tooltip: 'Einstellungen',
                handler(){
                    new GibsonOS.Window({
                        title: 'Homecontrol Modul ' + module.name + ' Einstellungen',
                        id: 'hcModuleSettingsWindow' + id,
                        width: 300,
                        autoHeight: true,
                        items: [
                            eval(module.helper + 'Settings(module, "' + id + '")')
                        ]
                    }).show();
                }
            }];
        }

        new GibsonOS.App({
            title: 'Homecontrol Modul: ' + module.name,
            id: 'hcModuleViewWindow' + id,
            width: module.settings && module.settings.width ? module.settings.width : 700,
            height: module.settings && module.settings.height ? module.settings.height : 400,
            maximizable: module.settings && module.settings.maximizable ? module.settings.maximizable : true,
            minimizable: module.settings && module.settings.minimizable ? module.settings.minimizable : true,
            closable: module.settings && module.settings.closable ? module.settings.closable : true,
            resizable: module.settings && module.settings.resizable ? module.settings.resizable : true,
            appIcon: module.settings && module.settings.icon ? module.settings.icon : 'icon_homecontrol',
            tools: settings,
            items: [{
                xtype: 'gosTabPanel',
                items: [
                    eval(module.helper + 'View(module, "' + id + '")'),
                    {
                        xtype: 'gosModuleHcIndexLogGrid',
                        gos: {
                            data: {
                                extraParams: {
                                    module: module.id
                                }
                            }
                        }
                    }
                ]
            }]
        }).show();
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
        let message = 'Möchten Sie die ' + records.length + ' Module wirklich löschen?';

        if (records.length === 1) {
            message = 'Möchten Sie das Modul ' + records[0].get('name') + ' wirklich löschen?';
        }

        GibsonOS.MessageBox.show({
            title: 'Module löschen?',
            msg: message,
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler() {
                    me.setLoading(true);
                    let ids = [];

                    Ext.iterate(records, (record) => {
                        ids.push(record.get('id'));
                    });

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/module',
                        method: 'DELETE',
                        params: {
                            'ids[]': ids
                        },
                        success() {
                            me.getStore().remove(records);
                        },
                        callback() {
                            me.setLoading(false);
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