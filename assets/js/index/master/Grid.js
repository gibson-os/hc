Ext.define('GibsonOS.module.hc.index.master.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcIndexMasterGrid'],
    multiSelect: true,
    enableDrag: true,
    getShortcuts(records) {
        let shortcuts = [];

        Ext.iterate(records, (record) => {
            shortcuts.push({
                id: null,
                module: 'hc',
                task: 'master',
                action: 'view',
                text: record.get('name'),
                icon: 'icon_homecontrol',
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

        me.store = new GibsonOS.module.hc.index.store.Master();

        me.callParent(arguments);
    },
    enterFunction(master) {
        new GibsonOS.module.hc.master.App({master: master.getData()});
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Protokoll',
            dataIndex: 'protocol',
            width: 100
        },{
            header: 'Adresse',
            dataIndex: 'address',
            width: 100
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