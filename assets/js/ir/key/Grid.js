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
            url: baseDir + 'hc/ir',
            method: 'POST',
            params:  {
                moduleId: me.moduleId,
                id: key.get('id')
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
                        keys.push({id: key.get('id')});
                    });

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/ir/keys',
                        method: 'DELETE',
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