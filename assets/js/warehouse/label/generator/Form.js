Ext.define('GibsonOS.module.hc.warehouse.label.generator.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelGeneratorForm'],
    overflowY: 'auto',
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Spaltenversatz',
            name: 'columnOffset',
            step: 1,
            minValue: 0,
            value: 0
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Zeilenversatz',
            name: 'rowOffset',
            step: 1,
            minValue: 0,
            value: 0
        },{
            xtype: 'gosCoreComponentFormFieldCheckbox',
            fieldLabel: 'Label pro Item',
            boxLabel: 'Für jedes Item wird ein Label erzeugt. Statt für jede Box.',
            name: 'labelPerItem',
            step: 1,
            minValue: 1
        }];

        me.buttons = [{
            xtype: 'gosButton',
            text: 'Generieren',
            handler() {
                const form = me.getForm();

                location.href =
                    baseDir + 'hc/warehouseLabel/generate' +
                    '/id/' + me.labelId +
                    '/moduleId/' + me.moduleId +
                    '/columnOffset/' + form.findField('columnOffset').getValue() +
                    '/rowOffset/' + form.findField('rowOffset').getValue() +
                    '/labelPerItem/' + form.findField('labelPerItem').getValue() +
                    '/warehouse_labels.pdf'
                ;
            }
        }];

        me.callParent();
    }
});