import React, { Component } from 'react'

class Form extends Component {
    constructor() {
        super();

        this.state = {
            name: ''
        };
    }

    inputHandler = e => {
        this.setState({
            [e.target.name]: e.target.value
        })
    };

    addTodohandler = (e, name) => {
        e.preventDefault();

        if (this.state.name.trim() === '') {
            return alert('Item name cannot be null');
        }

        this.setState({
            name: ''
        });

        this.props.addTodo(this.state.name);
    };

    render() {
        return (
            <form onSubmit={e => this.addTodohandler(e, this.state.name)}>
                <input type="text" name="name" value={this.state.name} onChange={this.inputHandler} autoComplete="off" />
                <button type="submit">Submit</button>
            </form>
        )
    }
}

export default Form
