import React, { Component } from 'react';
import Popover from '@material-ui/core/Popover';

export default class PiOnModule extends Component {

    constructor(props) {
        super(props);
        this.state = { anchorEl: null };

        this.handleShowValuePopup = this.handleShowValuePopup.bind(this);
        this.handleHideValuePopup = this.handleHideValuePopup.bind(this);
    }

    handleShowValuePopup(event) {
        console.log("show popup!");
        this.setState({ anchorEl: event.currentTarget });
    }

    handleHideValuePopup(event) {
        console.log("hide popup!");
        this.setState({ anchorEl: null });
    }

    render() {
        let ts_string;
        if (this.props.value.timestamp !== undefined) {
            var val_timestamp = new Date(this.props.value.timestamp * 1000);
            let year = "" + val_timestamp.getFullYear();
            let month = ("" + (val_timestamp.getMonth() + 1)).padStart(2, '0');
            let day = ("" + val_timestamp.getDate()).padStart(2, '0');
            let hours = ("" + val_timestamp.getHours()).padStart(2, '0');
            let mins = ("" + val_timestamp.getMinutes()).padStart(2, '0');
            let secs = ("" + val_timestamp.getSeconds()).padStart(2, '0');
            ts_string = hours + ":" + mins + ":" + secs + " " + year + "/" + month + "/" + day;
        }
        const valuePopup = <Popover
            anchorEl={this.state.anchorEl}
            onClose={this.handleHideValuePopup}
            open={this.state.anchorEl !== null}
            anchorOrigin={{
                vertical: 'bottom',
                horizontal: 'center',
            }}
        >
            <div>data: {this.props.value.data}</div>
            <div>has_error: {this.props.value.has_error ? "true" : "false"}</div>
            <div>error_msg: {this.props.value.error_msg}</div>
            <div>timestamp: {ts_string}</div>
            <div>certainty: {this.props.value.certainty }</div>
            <div>initialised: {this.props.value.initialised ? "true" : "false"}</div>
        </Popover>;

        const CustomTag = this.props.type;
        return <span>
            <span className="itemname" onClick={this.handleShowValuePopup}>{this.props.item_name}</span>
            {valuePopup}
            <span className="itemvalue">
                <CustomTag item_name={this.props.item_name}
                    value={this.props.value}
                    onValueChange={this.props.onValueChange}
                    all_values={this.props.all_values}
                    subscribeItem={this.props.subscribeItem}
                ></CustomTag>
            </span>
        </span >;
    }
}