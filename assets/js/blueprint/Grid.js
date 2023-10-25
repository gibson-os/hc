Ext.define('GibsonOS.module.hc.blueprint.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleHcBlueprintGrid'],
    autoScroll: true,
    multiSelect: true,
    enterButton: {
        iconCls: 'icon_system system_show',
    },
    enterFunction(blueprint) {
        new GibsonOS.module.hc.blueprint.App({
            blueprintId: blueprint.get('id')
        });
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.blueprint.store.Blueprint();

        me.callParent();
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        }];
    }
});