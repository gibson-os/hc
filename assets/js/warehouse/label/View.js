Ext.define('GibsonOS.module.hc.warehouse.label.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcWarehouseLabelView'],
    multiSelect: false,
    trackOver: true,
    itemSelector: 'div.hcWarehouseLabelElement',
    selectedItemCls: 'hcWarehouseLabelElementSelected',
    overItemCls: 'hcWarehouseLabelElementHover',
    overflowX: 'auto',
    overflowY: 'auto',
    initComponent() {
        const me = this;

        me.tpl = new Ext.XTemplate(
            '<div class="hcWarehouseLabelSize">{[this.labelSize()]}</div>',
            '<div class="hcWarehouseLabel" style="{[this.labelStyle()]}">',
            '<tpl for=".">',
            '<div ',
            'class="hcWarehouseLabelElement" ',
            'style="height: {height}mm; width: {width}mm; top: {top}mm; left: {left}mm;" ',
            'title="',
                'Breite: {width}mm&#10;',
                'Höhe: {height}mm&#10;',
                'Links: {left}mm ({[(values.left+values.width).toFixed(2)]}mm)&#10;',
                'Oben: {top}mm ({[(values.top+values.height).toFixed(2)]}mm)',
            '"',
            '>{type}</div>',
            '</tpl>',
            '</div>',
            {
                labelStyle() {
                    const template = me.getStore().getProxy().getReader().jsonData.template;

                    return 'width: ' + template.itemWidth + 'mm; height: ' + template.itemHeight + 'mm;';
                },
                labelSize() {
                    const template = me.getStore().getProxy().getReader().jsonData.template;

                    return template.itemWidth + 'mm X ' + template.itemHeight + 'mm';
                }
            }
        );

        me.store = new GibsonOS.module.hc.warehouse.store.label.Element({
            labelId: me.labelId
        });

        me.callParent();
    }
});