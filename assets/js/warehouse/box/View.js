Ext.define('GibsonOS.module.hc.warehouse.box.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcWarehouseBoxView'],
    multiSelect: true,
    trackOver: true,
    itemSelector: 'div.hcWarehouseBox',
    selectedItemCls: 'hcWarehouseBoxSelected',
    overItemCls: 'hcWarehouseBoxHover',
    gridSize: 30,
    offsetTop: 6,
    offsetLeft: 6,
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.warehouse.store.View({
            moduleId: me.moduleId
        });

        const id = Ext.id();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div ',
                    'id="' + id + '{id}" ',
                    'class="hcWarehouseBox" ',
                    'style="',
                        'left: {left*' + me.gridSize + '+' + me.offsetLeft + '}px; ',
                        'top: {top*' + me.gridSize + '+' + me.offsetTop + '}px; ',
                        'width: {width*' + me.gridSize + '}px; ',
                        'height: {height*' + me.gridSize + '}px;',
                        '">',
                    '{name}',
                '</div>',
            '</tpl>'
        );


        me.callParent();
    }
});