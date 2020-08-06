var mongoose = require('mongoose');
var Todo = require('../models/Todo');

class TodoController {
    index(req, res)
    {
        Todo.find()
        .then((todos) => {
            res.json(todos);
        });
    }

    add(req, res)
    {
        Todo.create({
            title: req.body.title,
            completed: false
        }, (err, todo) => {
            if (err) return handleError(err);

            res.json(todo);
        });
    }

    delete(req, res)
    {
        Todo.findByIdAndDelete(req.params.id, (err, todo) => {
            if (err) return handleError(err);

            res.json(todo);
        });
    }
}

module.exports = new TodoController;