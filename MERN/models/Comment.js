const mongoose = require('mongoose');

const Comment = mongoose.Schema({
    author: String,
    body: String,
    created_at: Date
});

module.exports = mongoose.model('Comment', Comment);