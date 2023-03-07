import { AMQPWebSocketClient } from './amqp-websocket-client.js'

var jwtPayload;
var routingKey;
var pairingKey;

var scannerStatusTimer=0;
var scannerTTL=0;
var conn;
var ch;
var amqp;
var rabbitErrorCount=0;
var disableKeyUpdate=false;
var disableRabbitCheck=false;
const rabbitReconnectTime=10
const rabbitCheckTime=1
const scannerCheckTime=1
const rabbitErrorMax=20

export function load() {
    rabbitInit();
    $("#barcodeScannerStatus").unbind('click').click(button_connect_click);
    console.log("Initialization complete");
    return true;
}

function rabbitInit() {
    start();
}

function genNewRoutingKey() {
    let xPairKey=String(Math.floor(Math.random()*(10**15))).padStart(9,"0");
    setRoutingKey(genRoutingKey(xPairKey),xPairKey);
}

function genRoutingKey(xparingKey) {
  pairingKey=xparingKey;
  var hash = CryptoJS.SHA256(xparingKey);
  return (hash.toString().substr(5,20));
}

function updateScannerStatus() {
  if (scannerTTL>0) {
    scannerStatusTimer++;
    if (scannerStatusTimer > 1 && scannerStatusTimer<=scannerTTL) {
      $("#barcodeScannerStatus").prop("title","Barcode ON-LINE");
      $("#barcodeScannerStatus i").show()
        .removeClass("inactive")
        .addClass("active")
        .removeClass("highlighted")
        .removeClass("fail")

    } else if (scannerStatusTimer>scannerTTL) {
      $("#barcodeScannerStatus").prop("title","Barcode OFFLINE");
      $("#barcodeScannerStatus i")
        .addClass("inactive")
        .removeClass("active")
        .removeClass("highlighted")
        .removeClass("fail")
    }
  } else {
    $("#barcodeScannerStatus").prop("title","Barcode OFFLINE");
    $("#barcodeScannerStatus i")
        .addClass("inactive")
        .removeClass("active")
        .removeClass("highlighted")
        .removeClass("fail")

    scannerStatusTimer=0;
  }
  setTimeout(updateScannerStatus, scannerCheckTime*1000);
}

async function checkRabbit() {
    if (disableRabbitCheck) {
        return;
    }
    if (!ch.closed 
        && !conn.closed 
        && ch.connection.socket.readyState===ch.connection.socket.OPEN)
    {
        setTimeout(checkRabbit, rabbitCheckTime*1000);
    } else {

        console.error("Error", "Socket or connection is closed", "reconnecting in "+(rabbitReconnectTime*rabbitErrorCount)+"s") 
        onRabbitDisconnected()
        setTimeout(start, (rabbitReconnectTime*rabbitErrorCount))
    }
  }

function setRoutingKey(newKey, xPairKey) {
  scannerTTL=0;
  updateScannerStatus();
  if (!newKey) {
    return;
  }
  routingKey=newKey;
  localStorage.setItem("foxBarcodeRoutingKey",routingKey);
  if (xPairKey) {
    $("#pairing_key").val(xPairKey);
  }
  $("#routingKey").val(routingKey);
}

function updateBarcode() {
    API.exec({
        skipSessionCheck: true,
        requestType: "POST",
        method: "core/foxBarcode",
        noblank: true,
        data: {
            type: "pdf417",
            code: "fxc"+routingKey+"xf"
        },
        onSuccess: function(json) {
            $("#pairingBarcode").attr("src","data:image/png;base64,"+json.data);
            return false;
        }
    })
}

function showWaitDialog() {
    UI.showInfoDialog("Update not allowed now. Please wait about 5 sec");
}
function button_newcode_click() {
    if (disableKeyUpdate) { showWaitDialog(); return; }
    $("#pairing_key").val("");
    button_refresh_click();
    updateBarcode();
}

function button_refresh_click() {
    if (disableKeyUpdate) { showWaitDialog(); return; }
    let xpk=$("#pairing_key").val();
    if (xpk) {
        let xrk=genRoutingKey(xpk);
        setRoutingKey(xrk);
    } else {
        genNewRoutingKey(true);
    }
    updateBarcode();
    start();
}

function button_connect_click() {
    let buttons={};
    buttons["Connect"]=function() { button_refresh_click(); }
    buttons["New code"]=function() { button_newcode_click(); }
    buttons["Cancel"]=function() { UI.closeDialog('barcodeconnect'); }

    UI.createDialog(
        UI.addFieldGroup([
            UI.addField({
                title: "PairingCode",
                item: 
                $("<img>",{
                    id: 'pairingBarcode',
                })
            }),
    
            UI.addField({
                title: "pairing key",
                item: "pairing_key",
                type: "input",
            }),
            UI.addField({
                title: "routingKey",
                item: "routingKey",
                type: "input",
                disabled: true
            }),
        ]),
	"Connect scanner", 
	buttons,
	400,1,'barcodeconnect');
	updateBarcode();
	$("#pairing_key").val(pairingKey);
    $("#routingKey").val(routingKey);
	UI.openDialog('barcodeconnect')
}

async function start() {
    disableRabbitCheck=true;
    disableKeyUpdate=true;
    
    if (localStorage.getItem("foxBarcodeRoutingKey")) {
        setRoutingKey(localStorage.getItem("foxBarcodeRoutingKey"))
    } else {
        genNewRoutingKey();
    }

    if (!API.settings.get("rabbitMqEnabled")===true) {
        console.log("Rabbit Disabled for this instances");
        return;
    }
    if (rabbitErrorCount>rabbitErrorMax) {
        console.log("Rabbit connection failed after "+rabbitErrorMax+" attempts");
        return false;
    }

    const tls = (window.location.protocol === "https:" || window.location.scheme === "https:")
    const urlSuffix=API.settings.get("rabbitMqWS");
    if (urlSuffix.match(/^(ws|wss|http|https):\/\//)) {
        var url = `${urlSuffix}`
    } else {
        var url = `${tls ? "wss" : "ws"}://${window.location.host}${urlSuffix}`
    }

    jwtPayload=API.session.parseJwt();
    let tokeX=API.session.getJwt();

    amqp = new AMQPWebSocketClient(url, API.settings.get("rabbitMqVHost"), jwtPayload.user, tokeX)

    if (conn && !conn.closed && conn.socket.readyState===conn.socket.OPEN) {
      if (!ch.closed) { await ch.close(); }
      if (!conn.closed) { await conn.close(); }
      onRabbitDisconnected();
    }
    try {
      conn = await amqp.connect()
      ch = await conn.channel()
      disableRabbitCheck=false;
      setTimeout(checkRabbit, rabbitCheckTime*1000);
      setTimeout(function() { disableKeyUpdate=false; }, 5000);
      onRabbitConnected(ch)
      const q = await ch.queue("foxsid."+jwtPayload.foxsid, {autoDelete: true})
      const qm = await ch.queue("foxuid."+jwtPayload.sub, {autoDelete: false})

      await q.bind("fox.barcode", routingKey)
      await q.unbind("fox.barcode","*")
      const consumer = await q.subscribe({noAck: false}, (msg) => {
        if (msg.exchange=="fox.barcode" && msg.routingKey==routingKey) {
          let msgData=(JSON.parse(msg.bodyToString()));
          scannerStatusTimer=0;
          
          if (msgData.timeout) {
              scannerTTL=msgData.timeout*3;
          }
          if (msgData.type=="service") {
            if (msgData.data != 'scannerReady') {
              textarea.value += "Barcode: "+msgData.data + "\n"
            }
          } else if (msgData.type=="code") {
            $("#barcodeScannerStatus").prop("title","Barcode ACTIVE");
            $("#barcodeScannerStatus i")//.show()
                .removeClass("inactive")
                .removeClass("active")
                .addClass("highlighted")
                .removeClass("fail")
            
    
            if (!document.hidden) {
                ($('input:focus')).val(msgData.data).change();
                var inputs = $(':input.i')
                var nextInput = inputs.get(inputs.index($('input:focus')) + 1);
                if (nextInput) {
                    nextInput.focus();
                }
            }
          }
          
        } else {
          textarea.value += msg.bodyToString() + "\n"
        }
        msg.ack()
      })
    } catch (err) {
      console.error("Error", err, "reconnecting in "+(rabbitReconnectTime*rabbitErrorCount)+"s")
      onRabbitDisconnected()
      setTimeout(start, (rabbitReconnectTime*rabbitErrorCount)*1000)
    }
}

function onRabbitConnected(ch) {
    console.log("Rabbit connected");
    rabbitErrorCount=0;

    $("#barcodeScannerStatus").show();
    $("#newMsgAlert")
        .show()
        .prop("title","ON-LINE");
    $("#newMsgAlert i")
        .removeClass("fa-bell-slash")
        .addClass("fa-bell")
        .removeClass("inactive")
        .addClass("active")
        .removeClass("highlighted")
        .removeClass("fail")
    
}

function onRabbitDisconnected() {
    rabbitErrorCount++;
    console.log("Rabbit disconnected");

    $("#barcodeScannerStatus").hide();
    $("#newMsgAlert")
        .show()
        .prop("title","OFFLINE");
    $("#newMsgAlert i")
        .removeClass("fa-bell")
        .addClass("fa-bell-slash")
        .removeClass("inactive")
        .removeClass("active")
        .removeClass("highlighted")
        .addClass("fail")
    
}

