Ext.define('GibsonOS.module.hc.ir.key.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcIrKeyForm'],
    requiredPermission: {
        action: 'key',
        method: 'POST',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    lastLogId: null,
    keyId: null,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'name',
            fieldLabel: 'Name'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            name: 'protocolName',
            fieldLabel: 'Protokoll'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            name: 'address',
            fieldLabel: 'Adresse'
        },{
            xtype: 'gosCoreComponentFormFieldDisplay',
            name: 'command',
            fieldLabel: 'Kommando'
        },{
            xtype: 'gosFormHidden',
            name: 'protocol',
        }];

        me.buttons = [{
            xtype: 'gosButton',
            itemId: 'saveButton',
            text: 'Speichern',
            handler: function() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/ir/key',
                    method: 'POST',
                    params: {
                        id: me.keyId ?? 0,
                        address: me.getForm().findField('address').getValue(),
                        command: me.getForm().findField('command').getValue()
                    },
                    success() {
                        me.reset();
                    }
                });
            }
        },{
            xtype: 'gosButton',
            itemId: 'resetButton',
            text: 'Verwerfen',
            handler() {
                me.reset();
            }
        }];

        me.callParent();

        me.on('afterrender', function() {
            me.loadMask = new Ext.LoadMask(me, {
                msg: 'Warte auf Eingabe',
                formBind: true
            });
            me.reset();
        });
    },
    reset() {
        const me = this;

        me.lastLogId = null;
        me.keyId = null;
        me.loadMask.show();
        me.getForm().reset();
        me.waitForKey();
    },
    waitForKey() {
        const me = this;

        const runRequest = function() {
            GibsonOS.Ajax.request({
                url: baseDir + 'hc/ir/waitForKey',
                method: 'GET',
                params: {
                    moduleId: me.moduleId,
                    lastLogId: me.lastLogId
                },
                messageBox: {
                    buttonHandler(button, response) {
                        me.lastLogId = null;

                        if (!me.isVisible()) {
                            return;
                        }

                        if (button.value) {
                            const key = Ext.decode(response.responseText).data.key;
                            me.getForm().setValues(key);
                            me.keyId = key.id;
                            me.loadMask.hide();

                            return;
                        }

                        setTimeout(runRequest, 1000);
                    }
                },
                success(response) {
                    const data = Ext.decode(response.responseText).data;
                    me.lastLogId = data.lastLogId;

                    if (data.key) {
                        me.getForm().setValues(data.key);
                        me.keyId = null;
                        me.loadMask.hide();
                    } else {
                        if (!me.isVisible()) {
                           return;
                        }

                        setTimeout(runRequest, 10);
                    }
                }
            });
        };
        runRequest();
    }
});