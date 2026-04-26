class Team:
    """
    Basic class to define a team.
    A team consists of a collection of players.
    """

    def __init__(self, name: str, players: list):
        """
        Constructor.
        Args:
            name (str): Name of the team.
            players (list): A list of Player objects.
        """
        self.name = name
        self.players = players
        self.points = 0
        self.wins = 0
        self.league_points = 0

    def get_total_experience(self):
        """
        Calculate the team's total experience.
        Sum of experiences of all players.
        Returns:
            int: The total experience score.
        """
        count = 0
        for p in self.players:
            count += p.experience
        return count
