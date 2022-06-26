Ext.define('GibsonOS.module.hc.warehouse.box.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosTabPanel',
            items: [{
                xtype: 'gosCoreComponentPanel',
                title: 'Bild',
                itemId: 'image',
                height: 295,
                data: {
                    name: '',
                    image: '',
                    src: ''
                },
                tpl: new Ext.XTemplate(
                    '<img src="<tpl if="src">{src}<tpl else>{image}</tpl>" alt="{name}" />'
                )
            },{
                xtype: 'gosCoreComponentPanel',
                title: 'Code',
                itemId: 'codeImage',
                height: 295,
                data: {
                    name: '',
                    code: ''
                },
                tpl: new Ext.XTemplate(
                    '<img src="{code}" alt="{name}" />'
                )
            }]
        },{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Anzahl',
            name: 'stock',
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldTextArea',
            fieldLabel: 'Beschreibung',
            name: 'description'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Breite',
            name: 'width',
            minValue: 1,
            maxValue: 25
        }, {
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Höhe',
            name: 'height',
            minValue: 1,
            maxValue: 25
        },{
            xtype: 'gosModuleHcWarehouseBoxTabPanel',
            moduleId: me.moduleId
        }];

        me.callParent();

        me.down('#image').on('render', () => {
            const element = me.getEl().dom;
            const stopEvents = (event) => {
                event.stopPropagation();
                event.preventDefault();
            };
            element.ondragover = stopEvents;
            element.ondrageleave = stopEvents;
            element.ondrop = (event) => {
                stopEvents(event);

                const file = event.dataTransfer.files[0];
                const reader = new FileReader();

                reader.readAsDataURL(file);
                reader.onload = () => {
                    let data = me.down('#image').data;
                    data.src = reader.result;
                    me.down('#image').update(data);
                };
            };
        });
    }
});