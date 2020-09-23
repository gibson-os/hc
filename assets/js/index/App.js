Ext.define('GibsonOS.module.hc.index.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcIndexApp'],
    id: 'homecontrol',
    title: 'Homecontrol',
    appIcon: 'icon_homecontrol',
    width: 700,
    height: 400,
    requiredPermission: {
        module: 'hc',
        task: 'index'
    },
    initComponent: function(arguments) {
        let me = this;

        me.items = [{
            xtype: 'gosTabPanel',
            items: [{
                xtype: 'gosModuleHcIndexMasterGrid',
                title: 'Master'
            },{
                xtype: 'gosModuleHcIndexModuleGrid',
                title: 'Module',
                tbar: [{
                    text: 'Hinzufügen',
                    menu: [{
                        text: 'Gruppe',
                        requiredPermission: {
                            task: 'group',
                            action: 'save',
                            permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
                        },
                        handler: function () {
                            new GibsonOS.module.hc.group.Window();
                        }
                    }],
                },{
                    iconCls: 'icon_system system_delete',
                    itemId: 'hcIndexModuleDeleteButton',
                    requiredPermission: {
                        task: 'module',
                        action: 'delete',
                        permission: GibsonOS.Permission.DELETE + GibsonOS.Permission.MANAGE
                    },
                    disabled: true,
                    handler: function() {
                        let button = this;
                        let grid = me.down('gosModuleHcIndexModuleGrid');
                        let record = grid.getSelectionModel().getSelection()[0];

                        GibsonOS.MessageBox.show({
                            title: 'Modul löschen?',
                            msg: 'Möchten Sie das Modul ' + record.get('name') + ' wirklich löchen?',
                            type: GibsonOS.MessageBox.type.QUESTION,
                            buttons: [{
                                text: 'Ja',
                                handler: function() {
                                    grid.setLoading(true);

                                    GibsonOS.Ajax.request({
                                        url: baseDir + 'hc/module/delete',
                                        params: {
                                            id: record.get('id')
                                        },
                                        success: function() {
                                            button.disable();
                                            grid.getStore().remove(record);
                                            grid.setLoading(false);
                                        }
                                    });
                                }
                            },{
                                text: 'Nein'
                            }]
                        });
                    }
                }]
            },{
                xtype: 'gosModuleHcIndexTypeGrid',
                title: 'Modul Typen'
            },{
                xtype: 'gosModuleHcCallbackGrid',
                title: 'Makros',
                itemId: 'hcIndexMacroGrid',
                gos: {
                    data: {
                        extraParams: {
                            macro: true
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcCallbackGrid',
                title: 'Zeitgesteuerte Anweisungen',
                itemId: 'hcIndexCallbackGrid',
                gos: {
                    data: {
                        extraParams: {
                            timer: true
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcIndexLogGrid'
            }]
        }];

        me.callParent(arguments);

        me.down('gosModuleHcIndexModuleGrid').getSelectionModel().on('selectionchange', function(selectionModel, records) {
            let deleteButton = me.down('#hcIndexModuleDeleteButton');

            if (records.length) {
                deleteButton.enable();
            } else {
                deleteButton.enable();
            }
        });
    }
});