Ext.define('GibsonOS.module.hc.ir.key.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrKeyGrid'],
    autoScroll: true,
    multiSelect: true,
    enterButton: {
        iconCls: 'icon_system system_play'
    },
    addFunction() {
        const me = this;

        new GibsonOS.module.hc.ir.key.Window({
            moduleId: me.moduleId,
            gridStore: me.store
        });
    },
    enterFunction(key) {
        const me = this;

        me.setLoading(true);

        GibsonOS.Ajax.request({
            url: baseDir + 'hc/ir/send',
            params:  {
                moduleId: me.moduleId,
                protocol: key.get('protocol'),
                address: key.get('address'),
                command: key.get('command'),
            },
            callback() {
                me.setLoading(false);
            }
        });
    },
    deleteFunction(records) {
        const me = this;

        GibsonOS.MessageBox.show({
            title: 'Wirklich löschen?',
            msg: 'Möchtest du die ' +
                (records.length === 1 ? 'Taste "' + records[0].get('name') + '"' : records.length + ' Tasten ') +
                ' wirklich löschen?',
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler() {
                    let keys = [];

                    Ext.iterate(records, (key) => {
                        keys.push({
                            protocol: key.get('protocol'),
                            address: key.get('address'),
                            command: key.get('command')
                        });
                    });

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/ir/deleteKeys',
                        params: {
                            keys: Ext.encode(keys)
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

        me.store = new GibsonOS.module.hc.ir.store.Key({
            moduleId: me.moduleId
        });

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Protokoll',
            dataIndex: 'protocolName',
            flex: 1
        },{
            header: 'Adresse',
            dataIndex: 'address',
            flex: 1
        },{
            header: 'Kommando',
            dataIndex: 'command',
            flex: 1
        }];
    }
});