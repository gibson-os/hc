Ext.define('GibsonOS.module.hc.ir.key.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrKeyGrid'],
    autoScroll: true,
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
                command: key.get('name'),
            },
            callback() {
                me.setLoading(false);
            }
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