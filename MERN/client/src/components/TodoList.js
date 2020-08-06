import React from 'react'
import TodoItem from './TodoItem';

function TodoList(props) {
    return (
        <React.Fragment>
            <ul className="todo-list">
                {(
                    props.todos.map(todo => <TodoItem key={todo._id} todo={todo} removeTodo={props.removeTodo} />)
                )}
            </ul>
        </React.Fragment>
    )
}

export default TodoList
