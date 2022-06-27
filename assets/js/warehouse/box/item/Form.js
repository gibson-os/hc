Ext.define('GibsonOS.module.hc.warehouse.box.item.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxItemForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentPanel',
            itemId: 'image',
            cls: 'coloredPanel',
            data: {
                name: '',
                image: '',
                src: ''
            },
            tpl: new Ext.XTemplate(
                '<div class="hcWarehouseBoxImage" style="height: 290px; background-image: url(<tpl if="src">{src}<tpl else>{image}</tpl>);"></div>'
            )
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
            xtype: 'gosModuleHcWarehouseBoxItemTabPanel',
            moduleId: me.moduleId
        }];

        me.callParent();

        me.down('#image').on('render', (imagePanel) => {
            const element = imagePanel.getEl().dom;
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