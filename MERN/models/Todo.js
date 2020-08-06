var mongoose = require('mongoose');

var Todo = new mongoose.Schema({
    title: String,
    completed: Boolean
});

module.exports = mongoose.model('Todo', Todo);