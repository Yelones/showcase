import React from 'react'

function TodoItem({todo, removeTodo}) {
    return (
        <React.Fragment>
            <li onClick={() => removeTodo(todo._id)}>{todo.title}</li>
        </React.Fragment>
    );
}

export default TodoItem
