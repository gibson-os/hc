Ext.define('GibsonOS.module.hc.warehouse.label.template.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelTemplateForm'],
    overflowY: 'auto',
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'name',
            fieldLabel: 'Name',
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Seitenbreite',
            name: 'pageWidth',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Seitenhöhe',
            name: 'pageHeight',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Spalten',
            name: 'columns',
            step: 1,
            minValue: 1
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Reihen',
            name: 'rows',
            step: 1,
            minValue: 1
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Abstand Links',
            name: 'marginLeft',
            step: 0.1,
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Abstand Oben',
            name: 'marginTop',
            step: 0.1,
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Label Breite',
            name: 'itemWidth',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Label Höhe',
            name: 'itemHeight',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Label Abstand Rechts',
            name: 'itemMarginRight',
            step: 0.1,
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Label Abstand Unten',
            name: 'itemMarginBottom',
            step: 0.1,
            minValue: 0
        }];

        me.callParent();
    }
});