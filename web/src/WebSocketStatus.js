import React, {useEffect, useState}  from 'react';

export default function WebSocketStatus(props){

    const [text_state_str, setText_state_str] = useState("");

    useEffect(() => {
        if(props.websocket !== null){
            const interval = setInterval(function () {
                let text_state = "";
                switch (props.websocket.readyState) {
                    case 0:
                        text_state = "connecting";
                        break;
                    case 1:
                        text_state = "connected";
                        break;
                    case 2:
                        text_state = "closing";
                        break;
                    case 3:
                        text_state = "closed";
                        break;
                    default:
                        throw Error(`Unknown websocket status: {props.websocket.readyState}`);
                }
                const current_date = new Date();
                const hours = "" + current_date.getHours();
                const mins = "" + current_date.getMinutes();
                const secs = "" + current_date.getSeconds();
                setText_state_str("Websocket is " + text_state + "@" + hours.padStart(2, "0") + ":" + mins.padStart(2, "0") + ":" + secs.padStart(2, "0"));                
            }, 1000);

            return () => {
                clearInterval(interval);
            }
        } else {
            setText_state_str("Websocket is initialising");
        }
    });

    
    return <div id="websocketstatus">{text_state_str}</div>
}

