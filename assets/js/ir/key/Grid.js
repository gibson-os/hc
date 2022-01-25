Ext.define('GibsonOS.module.hc.ir.key.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrKeyGrid'],
    autoScroll: true,
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