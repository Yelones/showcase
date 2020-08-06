import React, { useState, useEffect } from 'react';
import Form from './Form';
import TodoList from './TodoList';
import axios from 'axios';
import config from '../config';

function List() {
    const [todos, setTodos] = useState([]);

    useEffect(() => {
        axios.get(config.api_url + '/todo')
            .then(res => {
                setTodos(res.data);
            })
            .catch(err => console.log(err));
    }, [])

    const addTodo = title => {
        axios.post(config.api_url + '/todo/add', {title})
            .then(res => {
                setTodos([...todos, res.data]);
            })
            .catch(err => console.log(err));
    }

    const removeTodo = id => {
        axios.delete(config.api_url + '/todo/delete/' + id)
            .then(todo => {
                setTodos(todos.filter(todo => todo._id !== id));
            })
            .catch(err => console.log(err));
    };

    return (
        <React.Fragment>
            <Form addTodo={addTodo} />

            {todos.length > 0 ? (
                <TodoList todos={todos} removeTodo={removeTodo} />
            ) : (
                <React.Fragment>
                    <p>Add new item to the list with the form above</p>
                </React.Fragment>
            )}
        </React.Fragment>
    )
}

export default List
