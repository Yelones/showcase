var mongoose = require('mongoose');

var Lottery = new mongoose.Schema({
    date: Date,
    numbers: Array
});

module.exports = mongoose.model('Lottery', Lottery);