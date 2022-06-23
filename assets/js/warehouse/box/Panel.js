Ext.define('GibsonOS.module.hc.warehouse.box.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxPanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    addFunction() {
        const store = this.viewItem.getStore();
        let maxTop = 0;

        store.each((box) => {
            if (box.get('left') <= 3) {
                let top = box.get('top');
                let height = box.get('height');
                maxTop = (maxTop > top + height) ? maxTop : (top + height);
            }
        });

        store.add(new GibsonOS.module.hc.warehouse.model.Box({
            left: 0,
            top: maxTop,
            width: 3,
            height: 3
        }));
    },
    deleteFunction(records) {
        this.viewItem.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.warehouse.box.View({
            region: 'center',
            moduleId: me.hcModuleId,
            overflowX: 'auto',
            overflowY: 'auto'
        });

        me.items = [me.viewItem, {
            xtype: 'gosModuleHcWarehouseBoxForm',
            region: 'east',
            disabled: true,
            flex: 0,
            width: 300
        }];

        me.callParent();
    }
});