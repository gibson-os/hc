Ext.define('GibsonOS.module.hc.ir.remote.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcIrRemoteForm'],
    requiredPermission: {
        action: 'saveRemote',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        // Keys
        // Event
        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Breite',
            name: 'width',
            minValue: 1,
            maxValue: 10
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Höhe',
            name: 'height',
            minValue: 1,
            maxValue: 10
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: 'Rahmen',
            items: [{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusTopLeft',
                maxValue: 100
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderTop',
                boxLabel: 'Oben'
            },{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusTopRight',
                maxValue: 100
            }]
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: '&nbsp;',
            labelSeparator: '',
            items: [{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderLeft',
                boxLabel: 'Links'
            },{
                xtype: 'gosCoreComponentFormFieldDisplay'
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderRight',
                boxLabel: 'Rechts'
            }]
        },{
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: '&nbsp;',
            labelSeparator: '',
            items: [{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusBottomLeft',
                maxValue: 100
            },{
                xtype: 'gosCoreComponentFormFieldCheckbox',
                name: 'borderBottom',
                boxLabel: 'Unten'
            },{
                xtype: 'gosCoreComponentFormFieldNumberField',
                name: 'borderRadiusBottomRight',
                maxValue: 100
            }]
        }]

        me.callParent();
    }
});